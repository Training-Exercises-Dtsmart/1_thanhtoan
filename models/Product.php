<?php

namespace app\models;

use \app\models\base\Product as BaseProduct;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "products".
 */
class Product extends BaseProduct
{

    const STATUS_ACTIVE = 1;

    public function formName(): string
    {
        return '';
    }

    public static function getAllProducts(): ActiveDataProvider
    {
        $query = static::find();
        
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);
    }
}
