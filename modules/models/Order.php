<?php

namespace app\modules\models;

use app\models\Order as BaseOrder;

class Order extends BaseOrder
{
    const PENDING = 0;
    const PAID = 1;
    const DELIVERY = 2;
    const COMPLETED = 3;
    const CANCELLED = 4;

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