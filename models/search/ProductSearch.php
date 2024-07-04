<?php

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Product;

class ProductSearch extends Product
{
    public $keyword;
    public function rules()
    {
        return [
            [['id', 'category_id', 'price', 'discount_price', 'stock', 'view_count', 'status'], 'integer'],
            [['name', 'description', 'keyword'], 'safe'],
            // [['id', 'category_id'], 'integer'],
            // [['name'], 'number'],
            // ['keyword', 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Product::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);


        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $query->andFilterWhere(["or", ["LIKE", "name", $this->keyword], ['like', "description", $this->keyword]]);
        // ->andFilterWhere(['like', "description", $this->keyword]);
        return $dataProvider;
    }
}
