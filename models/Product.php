<?php

namespace app\models;

use \app\models\base\Product as BaseProduct;

/**
 * This is the model class for table "products".
 */
class Product extends BaseProduct
{

    const STATUS_ACTIVE = 1;
    public function formName()
    {
        return '';
    }
}
