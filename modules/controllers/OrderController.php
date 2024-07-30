<?php

namespace app\modules\controllers;

use app\modules\models\Order;
use Yii;
use app\modules\jobs\SendOrderConfirmationEmailJob;
use app\modules\models\form\OrderForm;
use app\modules\models\OrderItem;
use app\modules\models\OrderPayment;
use common\helpers\HttpStatusCodes;
use app\Controllers\Controller;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Json;
use yii\httpclient\Client;

class OrderController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['order', 'callback'],
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['checkout'],
                    'roles' => ['author'],
                ],
                [
                    'allow' => true,
                    'actions' => ['order', 'callback'],
                    'roles' => ['?'],
                ],
            ]
        ];

        return $behaviors;
    }

    public function actionCheckout(): array
    {
        $cart = Yii::$app->request->post('items', []);
        if (empty($cart)) {
            return $this->json(false, [], 'Cart is empty', HttpStatusCodes::BAD_REQUEST);
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $orderForm = new OrderForm();
            $orderForm->total_amount = array_reduce($cart, function ($carry, $product) {
                return $carry + ($product['price'] * $product['quantity']);
            }, 0);

            $orderForm->user_id = Yii::$app->user->id;
            $orderForm->order_code = uniqid('order_');
            $orderForm->load(Yii::$app->request->post());
            if (!$orderForm->validate()) {
                return $this->json(false, $orderForm->getErrors(), HttpStatusCodes::BAD_REQUEST);
            }
            if (!$orderForm->save()) {
                return $this->json(false, $orderForm->getErrors(), HttpStatusCodes::BAD_REQUEST);
            }
            // add orderItem
            foreach ($cart as $product) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $orderForm->id;
                $orderItem->product_id = $product['id'];
                $orderItem->name = $product['name'];
                $orderItem->quantity = $product['quantity'];
                $orderItem->price = $product['price'];
                if (!$orderItem->save()) {
                    return $this->json(false, $orderItem->getErrors(), "Order item creation failed",
                        HttpStatusCodes::INTERNAL_SERVER_ERROR);
                }
            }
            //check payment methods
            $paymentMethods = Yii::$app->request->post('payment_methods', []);
            if (empty($paymentMethods)) {
                return $this->json(false, [], "No payment methods provided", HttpStatusCodes::BAD_REQUEST);
            }

            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod['method'] == 'zalopay') {
                    // ZaloPay payment integration
                    $client = new Client();
                    $config = [
                        "app_id" => 2553,
                        "key1" => "PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL",
                        "key2" => "kLtgPl8HHhfvMuDHPwKfgfsY4Ydm9eIz",
                        "endpoint" => "https://sb-openapi.zalopay.vn/v2/create",
                        "callback_url" => "http://localhost:8080/api/order/callback"
                    ];
                    $embeddata = '{}';
                    $items = '[]';
                    $transID = rand(0, 1000000);
                    $order = [
                        "app_id" => $config["app_id"],
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
                        "callback_url" => $config["callback_url"]
                    ];

                    $data = $order["app_id"] . "|" . $order["app_trans_id"] . "|" . $order["app_user"] . "|" . $order["amount"]
                        . "|" . $order["app_time"] . "|" . $order["embed_data"] . "|" . $order["item"];

                    $order["mac"] = hash_hmac("sha256", $data, $config["key1"]);
                    $response = $client->post('https://sb-openapi.zalopay.vn/v2/create', $order)->send();
                    $responseBody = $response->data;
                    if ($responseBody['return_code'] != 1) {
                        throw new \Exception('ZaloPay API error: ' . $responseBody['return_message']);
                    }

                    // Save the order payment
                    $orderPayment = new OrderPayment();
                    $orderPayment->order_id = $orderForm->id;
                    $orderPayment->payment_method = $paymentMethod['method'];
                    $orderPayment->amount = $paymentMethod['amount'];
                    $orderPayment->transaction_id = $responseBody['zp_trans_token'];
                    $orderPayment->status = $orderPayment::PENDING;
                    if (!$orderPayment->save()) {
                        return $this->json(false, $orderPayment->getErrors(), "Order payment creation failed",
                            HttpStatusCodes::INTERNAL_SERVER_ERROR);
                    }
                }
                //if payment is not zalopay
                $orderPayment = new OrderPayment();
                $orderPayment->order_id = $orderForm->id;
                $orderPayment->payment_method = $paymentMethod['method'];
                $orderPayment->amount = $paymentMethod['amount'];
                $orderPayment->transaction_id = uniqid('txn_');
                $orderPayment->status = $orderPayment::PENDING;
                if (!$orderPayment->save()) {
                    return $this->json(false, $orderPayment->getErrors(), "Order payment creation failed",
                        HttpStatusCodes::INTERNAL_SERVER_ERROR);
                }
            }

            $transaction->commit();
            $orderItems = OrderItem::find()->where(['order_id' => $orderForm->id])->all();
            Yii::$app->queue->push(new SendOrderConfirmationEmailJob([
                'orderDetails' => $orderForm,
                'email' => $orderForm->customer_email,
                'listItems' => $orderItems,
            ]));
            return $this->json(true, ['order' => $orderForm, 'zalopay_response' => $responseBody],
                "Checkout successfully", HttpStatusCodes::OK);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->json(false, [], "Checkout failed: " . $e->getMessage(),
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @throws Exception
     */
    public function actionCallback(): array
    {
        $key2 = "eG4r0GcoNtRGbO8"; // Thay thế bằng key2 của bạn
        $postdata = file_get_contents('php://input');
        $postdatajson = Json::decode($postdata);
        $mac = hash_hmac("sha256", $postdatajson["data"], $key2);
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
            $order = Order::findOne(['order_code' => $appTransId]);
            if ($order) {
                $order->status = $order::PAID;
                $order->save();
                // update status order_payment
                $orderPayment = OrderPayment::findOne(['order_id' => $order->id]);
                if ($orderPayment) {
                    $orderPayment->status = $order::PAID;
                    $orderPayment->save();
                }
            }
            $result["return_code"] = 1;
            $result["return_message"] = "success";
        }
        return $result;
    }
}