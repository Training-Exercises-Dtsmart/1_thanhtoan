<?php

namespace app\modules\models\form;

use app\modules\models\Product;

class ProductUpdateForm extends Product
{
    public $images;

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['images'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg', 'maxFiles' => 10],
        ]);
    }
}