<?php

namespace app\modules\controllers;

use app\modules\models\form\OrderForm;
use app\modules\models\OrderItem;
use app\modules\models\OrderPayment;
use common\helpers\HttpStatusCodes;
use Yii;
use app\Controllers\Controller;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;

class OrderController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['checkout'],
                    'roles' => ['author'],
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
                $orderPayment = new OrderPayment();
                $orderPayment->order_id = $orderForm->id;
                $orderPayment->payment_method = $paymentMethod['method'];
                $orderPayment->amount = $paymentMethod['amount'];
                $orderPayment->transaction_id = uniqid('txn_');
                $orderPayment->status = 0; // pending
                if (!$orderPayment->save()) {
                    return $this->json(false, $orderPayment->getErrors(), "Order payment creation failed",
                        HttpStatusCodes::INTERNAL_SERVER_ERROR);
                }
            }

            $transaction->commit();
            $orderForm->refresh();
            return $this->json(true, $orderForm, "Checkout successful", HttpStatusCodes::OK);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->json(false, [], "Checkout failed: " . $e->getMessage(),
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
    }
}