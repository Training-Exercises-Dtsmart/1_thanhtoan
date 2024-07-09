<?php

namespace app\models\search;

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

        $query->andFilterWhere(["OR", ["LIKE", "name", $this->keyword], ['like', "description", $this->keyword]]);
        // different writing style
        // $query->andFilterWhere(['like', 'name', $this->keyword])
        // ->orFilterWhere(['like', 'description', $this->keyword]);
        return $dataProvider;
    }
}
