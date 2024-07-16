<?php

namespace app\models\search;

use yii\data\ActiveDataProvider;
use app\models\Product;

//use app\components\Pagination;
use Yii;

class ProductSearch extends Product
{
    public $keyword;

    public function rules(): array
    {
        return [
            [['id', 'category_id', 'price', 'discount_price', 'stock', 'view_count', 'status'], 'integer'],
            [['name', 'description', 'keyword', 'category_id'], 'safe'],
        ];
    }

    public function search($params): ActiveDataProvider
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
            'category_id' => $this->category_id,
        ]);

        $query->andFilterWhere(["OR", ["LIKE", "name", $this->keyword], ['like', "description", $this->keyword]]);
        // different writing style
        // $query->andFilterWhere(['like', 'name', $this->keyword])
        // ->orFilterWhere(['like', 'description', $this->keyword]);
        return $dataProvider;
    }
}
