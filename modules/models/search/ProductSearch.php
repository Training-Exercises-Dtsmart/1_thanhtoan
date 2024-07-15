<?php

namespace app\modules\models\search;

use yii\data\ActiveDataProvider;
use app\modules\models\Product;

class ProductSearch extends Product
{
    public $keyword;
    public $category_name;

    public function rules(): array
    {
        return [
            [['id', 'category_id', 'price', 'discount_price', 'stock', 'view_count', 'status'], 'integer'],
            [['category_name', 'keyword'], 'safe'],
        ];
    }

    public function search($params): ActiveDataProvider
    {
        $query = Product::find()->joinWith('category');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            "pagination" => [
                "pagesize" => 20,
            ],
        ]);
        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'category.name', $this->category_name])
            ->orFilterWhere(['like', 'product.name', $this->keyword]);
        return $dataProvider;
    }
}