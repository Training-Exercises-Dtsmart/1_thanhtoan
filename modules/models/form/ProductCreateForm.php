<?php

namespace app\modules\models\form;

use app\modules\models\Product;

class ProductCreateForm extends Product
{
    public $images;

    public function rules(): array
    {
        return [
            [['category_id'], 'required'],
            [['category_id'], 'integer'],
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['price'], 'double'],
            [['discount_price'], 'double'],
            [['stock'], 'integer'],
            [['description'], 'string'],
//            [['images'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg', 'jpeg', 'maxFiles' => 10],
            [['images'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg', 'maxFiles' => 10],

        ];
    }
}