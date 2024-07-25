<?php

namespace app\modules\models;

use app\models\Order as BaseOrder;

class Order extends BaseOrder
{
    public function fields()
    {
        return array_merge(parent::fields(), [
            'orderItems' => function ($model) {
                return $model->orderItems;
            },
            'paymentMethod' => function ($model) {
                return $model->orderPayments;
            }
        ]);
    }
}