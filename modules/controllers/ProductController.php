<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\Product;
use app\modules\models\form\ProductForm;
// use app\models\form\ProductForm;
use yii\data\ActiveDataProvider;
use yii\web\ServerErrorHttpException;
use app\modules\models\search\ProductSearch;

class ProductController extends Controller
{
    //pagination and SORT_DESC by created_at
    public function actionIndex()
    {
        $query =  Product::find();
        $provider = new ActiveDataProvider([
            "query" => $query,
            "pagination" => [
                "pageSize" => 2,
            ],
            "sort" => [
                "defaultOrder" => [
                    "created_at" => SORT_DESC,
                ],
            ]
        ]);

        $serializer = new \yii\rest\Serializer(["collectionEnvelope" => "items"]);
        $data = $serializer->serialize($provider);
        return $data;
    }

    // search by keyword or category_name


    public function actionProductSearch()
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return [
            'status' => 'success',
            'data' => $dataProvider->getModels(),
        ];

        // // join raw 
        // $product = Product::find()
        //     ->andFilterWhere(["LIKE", "categories.name", Yii::$app->request->getQueryParam("category_name")])
        //     ->leftJoin("categories", "products.category_id=categories.id")
        //     ->all();
        // return $this->json($product);


        // //join With
        // $product = Product::find()
        //     ->andFilterWhere(["LIKE", "categories.name", Yii::$app->request->getQueryParam("category_name")])
        //     ->joinWith("category")
        //     ->all();
        // return $this->json($product);
    }
    public function actionCreate()
    {
        $product = new ProductForm();
        $product->load(Yii::$app->request->post());
        if (!$product->validate() || !$product->save()) {
            return $this->json(false, [
                "errors" => $product->getErrors()
            ], "Can't update product", 400);
        }
        return $this->json(true, $product, "Success");
    }
    public function actionUpdate($product_id)
    {
        $product = Product::find()->where(["id" => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], "Product not found", 404);
        }
        $product->load(Yii::$app->request->post());
        if (!$product->validate() || !$product->save()) {
            $this->json(false, [], "Can't update product", 400);
        }
        return $this->json(true, $product, "update product successfully");
    }

    public function actionDelete($product_id)
    {
        $product = Product::find()->where(["id" => $product_id])->one();
        if (!$product) {
            return  $this->json(false, [], "Product not found", 404);
        }

        if ($product->delete()) {
            return $this->json(true, [], "Product deleted successfully", 200);
        } else {
            throw new ServerErrorHttpException('Failed to delete product');
        }
    }
}
