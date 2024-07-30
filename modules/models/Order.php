<?php

namespace app\modules\models;

use app\models\Order as BaseOrder;

class Order extends BaseOrder
{
    const PENDING = 0;
    const PAID = 1;
    const FAILED = 2;
    const CANCELLED = 3;

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