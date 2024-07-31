<?php

namespace app\modules\controllers;

use app\modules\models\Order;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\Exceptions\InvalidParameterException;
use PhpImap\Mailbox;
use Yii;
use app\modules\jobs\SendOrderConfirmationEmailJob;
use app\modules\models\form\OrderForm;
use app\modules\models\OrderItem;
use app\modules\models\OrderPayment;
use common\helpers\HttpStatusCodes;
use app\Controllers\Controller;
use yii\base\InvalidConfigException;
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
            'except' => ['order', 'callback', 'generate-qr', 'check-email', 'check-balance'],
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
                    'actions' => ['order', 'callback', 'generate-qr', 'check-email', 'check-balance'],
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

            $dataPayment = [];
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod['method'] == 'zalopay') {
                    // ZaloPay payment integration
                    $client = new Client();
                    $config = [
                        "app_id" => 2553,
                        "key1" => "PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL",
                        "key2" => "kLtgPl8HHhfvMuDHPwKfgfsY4Ydm9eIz",
                        "endpoint" => "https://sb-openapi.zalopay.vn/v2/create",
                        "callback_url" => "https://1efc-115-78-4-37.ngrok-free.app/api/order/callback"
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

                // Scan QR code payment integration
                if ($paymentMethod['method'] == 'scanqr') {
                    $qrcode = $this->actionGenerateQr($paymentMethod);
                    $dataPayment = $qrcode;
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
            return $this->json(true, ['order' => $orderForm, 'dataPayment' => $dataPayment],
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
        $key2 = "kLtgPl8HHhfvMuDHPwKfgfsY4Ydm9eIz"; // Thay thế bằng key2 của bạn
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

    /**
     * @throws \yii\httpclient\Exception
     * @throws InvalidConfigException
     */
    public function actionGenerateQr($infoQR)
    {
//        $json = file_get_contents('php://input');
//        $data = json::decode($json, true);
        $data = $infoQR;
        if (empty($data['accountNo']) || empty($data['accountName']) || empty($data['acqId']) || empty($data['amount']) || empty($data['addInfo'])) {
            return $this->asJson([
                'code' => '01',
                'desc' => 'Thiếu dữ liệu đầu vào'
            ]);
        }
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl('https://api.vietqr.io/v2/generate')
            ->setHeaders([
                'x-client-id' => 'ddb4e697-d658-44fb-9258-6737ff84070e',
                'x-api-key' => '48e88cd5-7617-4569-8ae6-aa4b436be36e',
                'Content-Type' => 'application/json',
            ])
            ->setContent(Json::encode([
                'accountNo' => $data['accountNo'],
                'accountName' => $data['accountName'],
                'acqId' => $data['acqId'],
                'amount' => $data['amount'],
                'addInfo' => $data['addInfo'],
                'template' => 'compact'
            ]))
            ->send();

        if ($response->isOk) {
            return $response->data;
        }
        return null;
    }


    /**
     * @throws InvalidParameterException
     */
    public function actionCheckBalance(): \yii\web\Response
    {
        $mailbox = new Mailbox(
            '{imap.gmail.com:993/imap/ssl}INBOX',
            'thanhtoan28740@gmail.com',
            'kcsh vptx hmim sbpn',
            __DIR__ . '/../../attachments',
            'UTF-8',
        );
        try {
            $mailsIds = $mailbox->sortMails(1, true, 'FROM "mailalert@acb.com.vn"');
        } catch (ConnectionException $ex) {
            return $this->asJson([
                'code' => '01',
                'desc' => 'Kết nối IMAP thất bại: ' . $ex->getMessage()
            ]);
        } catch (Exception $ex) {
            return $this->asJson([
                'code' => '02',
                'desc' => 'Lỗi xảy ra: ' . $ex->getMessage()
            ]);
        }

        foreach ($mailsIds as $mailId) {
            // read email
            $email = $mailbox->getMail($mailId);
            $body = $email->textHtml;
//            Strip HTML tags and decode special characters
            $plainBody = strip_tags($body);
            $plainBody = html_entity_decode($plainBody);
            if (preg_match('/SMART172005DT/', $plainBody)) {
                return $this->asJson([
                    'code' => '00',
                    'desc' => 'Found matching email',
                    'email' => [
                        'subject' => $email->subject,
                        'from' => $email->fromAddress,
                        'date' => $email->date,
                        'body' => $plainBody,
                    ]
                ]);
            }
        }
        return $this->asJson([
            'code' => '03',
            'desc' => 'No matching email found'
        ]);
    }

}