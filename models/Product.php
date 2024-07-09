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
    public function formName()
    {
        return '';
    }

    public static function getAllProducts()
    {
        $query = static::find();

        $dataProvider = new ActiveDataProvider([
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

        return $dataProvider;
    }
}
