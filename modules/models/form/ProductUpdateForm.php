<?php

namespace app\modules\models\form;

use app\modules\models\Product;

class ProductUpdateForm extends Product
{
    public $images;

    public function rules(): array
    {
        return [
            [['category_id'], 'integer'],
            [['user_id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['price'], 'double'],
            [['discount_price'], 'double'],
            [['stock'], 'integer'],
            [['description'], 'string'],
            [['status'], 'integer'],
            [['images'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg', 'maxFiles' => 10],
        ];
    }
}