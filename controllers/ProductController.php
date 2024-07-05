<?php

namespace app\controllers;

use app\models\form\ProductForm;
use app\models\Product;
use Yii;
// use yii\rest\Controller;
// fix here controller 
use app\controllers\Controller;
use yii\data\ActiveDataProvider;
use app\models\search\ProductSearch;
use yii\web\ServerErrorHttpException;

class ProductController extends controller
{
    public function actionIndex()
    {
        $query = Product::find();
        // return $products;
        $provider = new ActiveDataProvider([
            'query' => $query,
            // 'pagination' => [
            // 'pageSize' => 2,
            // ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_ASC,
                ]
            ],
        ]);
        // var_dump($provider->getPagination()->getPage());
        // die;
        $serializer = new \yii\rest\Serializer(["collectionEnvelope" => "items"]);
        $data = $serializer->serialize($provider);
        return $data;
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
        $product = Product::find()->where(['id' => $product_id])->one();
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
        $product = Product::find()->where(['id' => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], "Product not found", 404);
        }
        if ($product->delete()) {
            return $this->json(true, [], "Product deleted successfully", 200);
        } else {
            throw new ServerErrorHttpException('Failed to delete product');
        }
    }
    public function actionSearch()
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return [
            'status' => 'success',
            'data' => $dataProvider->getModels(),
        ];
    }


    // public function actionCreate()
    // {
    //     $formProduct = new ProductForm();
    //     $formProduct->load(Yii::$app->request->post());
    //     if ($formProduct->validate()) {
    //         $formProduct->save();
    //     } else {
    //         return $formProduct->getErrors();
    //     }
    // }

    // public function actionUpdate($product_id)
    // {
    //     $product = Product::find()->where(['id' => $product_id])->one();
    //     if ($product === null) {
    //         throw new NotFoundHttpException('Product not found');
    //     }
    // }

}
