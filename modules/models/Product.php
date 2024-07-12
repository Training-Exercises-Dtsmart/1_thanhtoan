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
        ]);
    }

    public function getCategoryName(): string
    {
        return isset($this->category) ? $this->category->name : "Product category not found";
    }
}
