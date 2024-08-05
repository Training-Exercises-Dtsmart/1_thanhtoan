<?php

namespace app\modules\models;

use app\models\OrderPayment as BaseOrderPayment;

class OrderPayment extends BaseOrderPayment
{
    const UNPAID = 0;
    const PAIED = 1;
    const REFUNDED = 2;


}