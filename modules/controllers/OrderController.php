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
            'except' => [
                'order',
                'callback',
                'generate-qr',
                'check-email',
                'check-balance',
                'query-status-order-zalopay',
                'refund-zalopay',
                'query-refund-status-zalopay'
            ],
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
                        'query-status-order-zalopay',
                        'refund-zalopay',
                        'query-refund-status-zalopay'
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
                    $dataPayment = Yii::$app->zalopay->createOrder($paymentMethod, $orderForm);
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
                $orderPayment->status = OrderPayment::UNPAID;
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

    public function actionCallback()
    {
        $postdata = file_get_contents('php://input');
        $result = Yii::$app->zalopay->handleCallBack($postdata);
        return $result;
    }

    public function actionQueryStatusOrderZalopay($app_trans_id): array
    {
        $order = Order::find()->where(['app_trans_id' => $app_trans_id])->one();
        if (!$order) {
            return $this->json(false, [], 'Order not found', HttpStatusCodes::NOT_FOUND);
        }
        $queryOrder = Yii::$app->zalopay->queryStatusOrder($order->app_trans_id);
        if (!$queryOrder) {
            return $this->json(false, [], 'Failed query status order', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $queryOrder, 'Query status order', HttpStatusCodes::OK);
    }

    public function actionRefundZalopay($zp_trans_id): array
    {
        $order = Order::find()->where(['zp_trans_id' => $zp_trans_id])->one();
        if (!$order) {
            return $this->json(false, [], 'Order not found', 404);
        }
        $order_payment = OrderPayment::find()->where(['order_id' => $order->id])->andWhere(['payment_method' => "zalopay"])->one();
        if (!$order_payment) {
            return $this->json(false, [], 'Order payment not found', HttpStatusCodes::NOT_FOUND);
        }
        $response = Yii::$app->zalopay->refundOrder($order->zp_trans_id, $order_payment->amount);
        if (!$response) {
            return $this->json(false, [], 'Refund fail status order', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $response, 'Refund status order', HttpStatusCodes::OK);
    }


    public function actionQueryRefundStatusZalopay($m_refund_id): array
    {
        $orderPayment = OrderPayment::find()->where(['m_refund_id' => $m_refund_id])->one();
        if (!$orderPayment) {
            return $this->json(false, [], 'Order payment not found', 404);
        }
        $response = Yii::$app->zalopay->queryStatusRefund($m_refund_id);
        if (!$response) {
            return $this->json(false, [], 'Query status refund fail', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $response, 'Query Refund status success', HttpStatusCodes::OK);
    }


    /**
     * @throws \yii\httpclient\Exception
     * @throws InvalidConfigException
     */
    public function actionGenerateQr($infoQR, $order_code)
    {
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
            ->setUrl(env('VIETQR_ENDPOINT_GENERATE'))
            ->setHeaders([
                'x-client-id' => env('VIETQR_CLIENT_ID'),
                'x-api-key' => env('VIETQR_API_KEY'),
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
     * @throws \Exception
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
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $timeMinus10Minutes = new DateTime('-10 minutes');
            // Reformat the time in IMAP format
            $since = $timeMinus10Minutes->format('d-M-Y');
            $mailsIds = $mailbox->sortMails(1, true, 'FROM "mailalert@acb.com.vn" SINCE "' . $since . '"');

        } catch (ConnectionException $ex) {
            return $this->json(false, $ex->getMessage(), 'Connect IMAP error', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        } catch (Exception $ex) {
            return $this->json(false, $ex->getMessage(), 'Server error', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }

        foreach ($mailsIds as $mailId) {
            // read email
            $email = $mailbox->getMail($mailId);
            //get email send date and compare
            $emailDate = new DateTime($email->date);
            if ($emailDate >= $timeMinus10Minutes) {
                $body = $email->textHtml;
                //Strip HTML tags and decode special characters
                $plainBody = strip_tags($body);
                $plainBody = html_entity_decode($plainBody);

                preg_match('/tài khoản\s*(\d+)/i', $plainBody, $account);
                preg_match('/Giao dịch mới nhất:(Ghi nợ|Ghi có)\s*([+-]?[\d,]+\.\d+\s*VND)/i', $plainBody,
                    $moneyContent);
                preg_match('/Nội dung giao dịch:\s*(.*?)[.]/', $plainBody, $emailOrderCode[1]);

                if (preg_match('/' . $orderCode . '/i', $emailOrderCode[1][0])) {
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
        }
        return $this->json(false, [], 'No matching email found', HttpStatusCodes::NOT_FOUND);
    }

    /**
     * @throws Exception
     */
    private function actionUpdateOrderStatus($order)
    {
        $order->status = Order::COMPLETED;
        $order->save();

        $orderPayment = OrderPayment::find()->where(['order_id' => $order->id])->one();
        if ($orderPayment) {
            $orderPayment->status = OrderPayment::PAIED;
            $orderPayment->save();
        }
    }
}