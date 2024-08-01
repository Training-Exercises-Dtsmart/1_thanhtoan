<?php

namespace app\modules\controllers;

use Cassandra\Date;
use schmunk42\giiant\generators\crud\providers\extensions\DateTimeProvider;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Json;
use yii\httpclient\Client;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\Exceptions\InvalidParameterException;
use PhpImap\Mailbox;
use common\helpers\HttpStatusCodes;
use app\modules\models\Order;
use DateTime;
use app\modules\jobs\SendOrderConfirmationEmailJob;
use app\modules\models\form\OrderForm;
use app\modules\models\OrderItem;
use app\modules\models\OrderPayment;
use app\Controllers\Controller;

class OrderController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['order', 'callback', 'generate-qr', 'check-email', 'check-balance', 'zalo-pay-status'],
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
                    'actions' => [
                        'order',
                        'callback',
                        'generate-qr',
                        'check-email',
                        'check-balance',
                        'zalo-pay-status'
                    ],
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
            $orderForm->order_code = uniqid('order') . 'DTSMART';
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
                    $dataPayment = $this->actionPaymentZalopay($paymentMethod, $orderForm);
                    break;
                }

                // Scan QR code payment integration
                if ($paymentMethod['method'] == 'scanqr') {
                    $dataPayment = $this->actionGenerateQr($paymentMethod, $orderForm->order_code);
                }

                //if payment is not zalopay,qrcode
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
        $key2 = env('KEY2_ZALOPAY'); // Thay thế bằng key2 của bạn
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
            $order = Order::findOne(['app_trans_id' => $appTransId]);
            if ($order) {
                $order->status = $order::PAID;
                $order->save();
                // update status order_payment
                $orderPayment = OrderPayment::find()->where(['order_id' => $order->id])->andWhere(['payment_method' => "zalopay"])->one();
                if ($orderPayment) {
                    $orderPayment->status = OrderPayment::PAID;
                    $orderPayment->save();
                }
            }
            $result["return_code"] = 1;
            $result["return_message"] = "success";
        }
        // Kiểm tra trạng thái giao dịch nếu chưa thành công
        if ($result["return_code"] != 1) {
            $statusResponse = $this->queryZaloPayStatus($datajson["app_trans_id"]);

            if ($statusResponse['return_code'] == 1) {
                // Giao dịch thành công
                $order = Order::findOne(['app_trans_id' => $appTransId]);
                if ($order) {
                    $order->status = $order::PAID;
                    $order->save();
                    $orderPayment = OrderPayment::find()->where(['order_id' => $order->id])->andWhere(['payment_method' => "zalopay"])->one();
                    if ($orderPayment) {
                        $orderPayment->status = OrderPayment::PAID;
                        $orderPayment->save();
                    }
                }
                $result["return_code"] = 1;
                $result["return_message"] = "success";
            } else {
                if ($statusResponse['return_code'] == 3) {
                    // Giao dịch đang xử lý hoặc chưa thanh toán
                    $result["return_code"] = 3;
                    $result["return_message"] = "Transaction is processing or not paid yet";
                } else {
                    // Giao dịch thất bại
                    $result["return_code"] = 2;
                    $result["return_message"] = "Transaction failed";
                }
            }
        }


        return $result;
    }

    /**
     * @throws \yii\httpclient\Exception
     * @throws InvalidConfigException
     */
    public function actionGenerateQr($infoQR, $order_code)
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
                'addInfo' => $data['addInfo'] . "  " . $order_code,
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
     * @throws Exception
     */
    public function actionCheckBalance(): array
    {
        $orderCode = Yii::$app->request->post('order_code');
        $order = Order::find()->where(['order_code' => $orderCode])->one();
        if (!$order) {
            return $this->json(false, [], 'Order not found', HttpStatusCodes::NOT_FOUND);
        }
        $mailbox = new Mailbox(
            '{imap.gmail.com:993/imap/ssl}INBOX',
            'thanhtoan28740@gmail.com',
            'kcsh vptx hmim sbpn',
            __DIR__ . '/../../attachments',
            'UTF-8',
        );
        try {
//            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $currentTime = date('d-M-Y');
            $mailsIds = $mailbox->sortMails(1, true,
                'FROM "mailalert@acb.com.vn" SINCE "' . $currentTime . '"');
        } catch (ConnectionException $ex) {
            return $this->json(false, $ex->getMessage(), 'Connect IMAP error', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        } catch (Exception $ex) {
            return $this->json(false, $ex->getMessage(), 'Server error', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }

        foreach ($mailsIds as $mailId) {
            // read email
            $email = $mailbox->getMail($mailId);
            $body = $email->textHtml;
//            Strip HTML tags and decode special characters
            $plainBody = strip_tags($body);
            $plainBody = html_entity_decode($plainBody);

            preg_match('/tài khoản\s*(\d+)/i', $plainBody, $account);
            preg_match('/Giao dịch mới nhất:(Ghi nợ|Ghi có)\s*([+-]?[\d,]+\.\d+\s*VND)/i', $plainBody,
                $moneyContent);
            preg_match('/Nội dung giao dịch:\s*(.*?)[.]/', $plainBody, $emailOrderCode[1]);
            if (preg_match('/' . $orderCode . '/', $emailOrderCode[1][0])) {
                //change status order and order_payment
                $this->actionUpdateOrderStatus($order);
                return $this->json(true, [
                    'Money transfer content' => [
                        'Beneficiary account number' => $account[1],
                        'Money' => $moneyContent[2],
                        'Content' => $emailOrderCode[1][0],
                        'date' => $email->date,
                    ]
                ], 'Email found matching order', HttpStatusCodes::OK);
            }
        }
        return $this->json(false, [], 'No matching email found', HttpStatusCodes::NOT_FOUND);
    }

    /**
     * @throws Exception
     */
    private function actionUpdateOrderStatus($order)
    {
        $order->status = Order::PAID;
        $order->save();

        $orderPayment = OrderPayment::find()->where(['order_id' => $order->id])->one();
        if ($orderPayment) {
            $orderPayment->status = orderPayment::PAID;
            $orderPayment->save();
        }
    }

    public function actionPaymentZalopay($paymentMethod, $orderForm): array
    {

//        if ($paymentMethod['method'] == 'zalopay') {
        // ZaloPay payment integration
        $client = new Client();
        $config = [
            "app_id" => 2553,
            "key1" => env('KEY1_ZALOPAY'),
            "key2" => env('KEY2_ZALOPAY'),
            "endpoint" => env('ENDPOINT_ZALOPAY') . 'create',
            "callback_url" => "https://2769-115-78-4-37.ngrok-free.app/api/order/callback"
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
        $orderPayment->status = $orderPayment::PENDING;
        if (!$orderPayment->save()) {
            return $this->json(false, $orderPayment->getErrors(), "Order payment creation failed",
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $responseBody;
    }

    public function queryZaloPayStatus($appTransId)
    {
        $client = new Client();
        $config = [
            "app_id" => 2553,
            "key1" => env('KEY1_ZALOPAY'),
            "key2" => env('KEY2_ZALOPAY'),
            "endpoint" => env('ENDPOINT_ZALOPAY') . 'query'
        ];

        $data = $config["app_id"] . "|" . $appTransId . "|" . $config["key1"]; // app_id|app_trans_id|key1
        $params = [
            "app_id" => $config["app_id"],
            "app_trans_id" => $appTransId,
            "mac" => hash_hmac("sha256", $data, $config["key1"])
        ];

        $response = $client->post($config["endpoint"], $params)->send();
        if (!$response->isOk) {
            throw new \Exception('ZaloPay API error: ' . $response->content);
        }

        return $response->data;
    }

    
}