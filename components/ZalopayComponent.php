<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\db\Exception;
use yii\helpers\Json;
use yii\httpclient\Client;
use app\modules\models\Order;
use app\modules\models\OrderPayment;
use common\helpers\HttpStatusCodes;

class ZalopayComponent extends Component
{
    public $appId;
    public $key1;
    public $key2;
    public $endpoint;


    public function createOrder($paymentMethod, $orderForm)
    {
        $client = new Client();
        $embeddata = '{}';
        $items = '[]';
        $transID = rand(0, 1000000);
        $order = [
            "app_id" => $this->appId,
            "app_time" => round(microtime(true) * 1000),
            // miliseconds
            "app_trans_id" => date("ymd") . "_" . $transID,
            // translation missing: vi.docs.shared.sample_code.comments.app_trans_id
            "app_user" => Yii::$app->user->identity->username,
            "item" => $items,
            "embed_data" => $embeddata,
            "amount" => $paymentMethod['amount'],
            "description" => "Dtsmart - Payment for the order #" . $orderForm->order_code,
            "bank_code" => "zalopayapp",
            "callback_url" => env('CALLBACK_URL_ZALOPAY')
        ];
        $data = $order["app_id"] . "|" . $order["app_trans_id"] . "|" . $order["app_user"] . "|" . $order["amount"]
            . "|" . $order["app_time"] . "|" . $order["embed_data"] . "|" . $order["item"];
        $order["mac"] = hash_hmac("sha256", $data, $this->key1);
        $response = $client->post("{$this->endpoint}/create", $order)->send();
        $responseBody = $response->data;

        $orderForm->app_trans_id = $order['app_trans_id'];
        $orderForm->save();
        if ($responseBody['return_code'] != 1) {
            throw new \Exception('ZaloPay API error: ' . $responseBody['return_message']);
        }

        // Save the order payment
        $orderPayment = new OrderPayment();
        $orderPayment->order_id = $orderForm->id;
        $orderPayment->payment_method = $paymentMethod['method'];
        $orderPayment->amount = $paymentMethod['amount'];
        $orderPayment->transaction_id = $responseBody['zp_trans_token'];
        $orderPayment->status = OrderPayment::UNPAID;
        if (!$orderPayment->save()) {
            return $this->json(false, $orderPayment->getErrors(), "Order payment creation failed",
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $responseBody;
    }

    /**
     * @throws Exception
     */
    public function handleCallback($data): array
    {
        $postdatajson = Json::decode($data);
        $mac = hash_hmac("sha256", $postdatajson["data"], $this->key2);
        $requestmac = $postdatajson["mac"];
        $result = [];
        if (strcmp($requestmac, $mac) != 0) {
            //callback invalid
            $result["return_code"] = -1;
            $result["return_message"] = "mac not equal";
        } else {
            //pyament success
            //merchant update status order
            $datajson = Json::decode($postdatajson["data"]);
            $appTransId = $datajson["app_trans_id"];
            $zpTransId = $datajson["zp_trans_id"];

            // update status order
            $order = Order::find()->where(['app_trans_id' => $appTransId])->one();
            if ($order) {
//                $order->status = Order::PAID;
                $order->zp_trans_id = $zpTransId;
                $order->save(false);
                // update status order_payment
                $orderPayment = OrderPayment::find()->where(['order_id' => $order->id])->andWhere(['payment_method' => "zalopay"])->one();
                if ($orderPayment) {
                    $orderPayment->status = OrderPayment::PAIED;
                    $orderPayment->save(false);
                }
            }
            $result["return_code"] = 1;
            $result["return_message"] = "success";
        }
        return $result;
    }

    public function queryStatusOrder($app_trans_id)
    {
        $data = $this->appId . "|" . $app_trans_id . "|" . $this->key1;
        $params = [
            "app_id" => $this->appId,
            "app_trans_id" => $app_trans_id,
            "mac" => hash_hmac("sha256", $data, $this->key1)
        ];
        $client = new Client();
        $response = $client->post($this->endpoint . '/query', $params,
            ['content-type' => 'application/x-www-form-urlencoded'])
            ->setFormat(Client::FORMAT_URLENCODED)
            ->send();
        if ($response->isOk) {
            return $response->data;
        }
        return null;
    }

    /**
     * @throws \yii\httpclient\Exception
     * @throws Exception
     */
    public function refundOrder($zp_trans_id, $amount)
    {
        $timestamp = round(microtime(true) * 1000); // miliseconds
        $uid = "$timestamp" . rand(111, 999); // unique id
        $params = [
            "app_id" => $this->appId,
            "m_refund_id" => date("ymd") . "_" . $this->appId . "_" . $uid,
            "timestamp" => $timestamp,
            "zp_trans_id" => $zp_trans_id,
            "amount" => $amount,
            "description" => "ZaloPay Intergration Demo"
        ];

        $data = $params["app_id"] . "|" . $params["zp_trans_id"] . "|" . $params["amount"]
            . "|" . $params["description"] . "|" . $params["timestamp"];
        $params["mac"] = hash_hmac("sha256", $data, $this->key1);
        $client = new Client();
        $response = $client->post($this->endpoint . '/refund', $params,
            ['content-type' => 'application/x-www-form-urlencoded'])
            ->setFormat(Client::FORMAT_URLENCODED)
            ->send();
        if ($response->isOk) {
            $order = Order::find()->where(['zp_trans_id' => $zp_trans_id])->one();
            $order->status = Order::CANCELLED;
            $order->save(false);
            if ($order) {
                $order_payment = OrderPayment::find()->where(['order_id' => $order->id])->andWhere(['payment_method' => 'zalopay'])->one();
                $order_payment->status = OrderPayment::REFUNDED;
                $order_payment->m_refund_id = $params["m_refund_id"];
                $order_payment->save(false);
            }
            return $response->data;
        }
        return null;
    }

    /**
     * @throws \yii\httpclient\Exception
     */
    public function queryStatusRefund($m_refund_id)
    {
        $timestamp = round(microtime(true) * 1000); // miliseconds
        $data = $this->appId . "|" . $m_refund_id . "|" . $timestamp; // app_id|m_refund_id|timestamp
        $params = [
            "app_id" => $this->appId,
            "timestamp" => $timestamp,
            "m_refund_id" => $m_refund_id,
            "mac" => hash_hmac("sha256", $data, $this->key1)
        ];
        $client = new Client();
        $response = $client->post($this->endpoint . '/query_refund', $params,
            ['content-type' => 'application/x-www-form-urlencoded'])
            ->setFormat(Client::FORMAT_URLENCODED)
            ->send();
        if ($response->isOk) {
            return $response->data;
        }
        return null;
    }
}