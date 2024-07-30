<?php

namespace app\modules\models;

use app\models\OrderPayment as BaseOrderPayment;

class OrderPayment extends BaseOrderPayment
{
    const PENDING = 0;
    const PAID = 1;

}