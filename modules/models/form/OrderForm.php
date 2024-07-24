<?php

namespace app\modules\models\form;

use app\modules\models\Order;

class OrderForm extends Order
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['shipping_address'], 'required'],
        ]);
    }
}