<?php

namespace app\modules\models;

use app\models\Product as BaseProduct;

class Product extends BaseProduct
{
    public function fields()
    {
        // return array(parent::fields(), 'name', 'description', 'price');
        return array_merge(parent::fields(), [
            "category_name" => "categoryName",
            // "name" => function () {
            // return strtolower($this->name);
            // }
        ]);
    }


    public function getCategoryName()
    {
        return $this->category->name;
    }
}
