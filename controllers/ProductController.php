<?php

namespace app\controllers;

use app\models\form\ProductForm;
use app\models\Product;
use Yii;
// use yii\rest\Controller;
// fix here controller 
use app\controllers\Controller;
use app\models\search\ProductSearch;
use common\helpers\HttpStatusCodes;

class ProductController extends controller
{
    //search for products
    public function actionIndex()
    {
        $dataProvider = Product::getAllProducts();
        $serializer = new \yii\rest\Serializer(['collectionEnvelope' => 'items']);
        $data = $serializer->serialize($dataProvider);
        return $this->json(true, $data, "Success", HttpStatusCodes::OK);
    }

    public function actionSearch()
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if (!$dataProvider->getModels()) {
            return $this->json(false, [], "No product found", HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $dataProvider->getModels(), "Search result", HttpStatusCodes::OK);
    }

    public function actionCreate()
    {
        $product = new ProductForm();
        $product->load(Yii::$app->request->post());
        if (!$product->validate() || !$product->save()) {
            return $this->json(false, ["error" => $product->getErrors()], "Can't update product", HttpStatusCodes::BAD_REQUEST);
        }
        return $this->json(true, $product, "Success", HttpStatusCodes::OK);
    }


    public function actionUpdate($product_id)
    {
        $product = Product::find()->where(['id' => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], "Product not found", HttpStatusCodes::NOT_FOUND);
        }
        $product->load(Yii::$app->request->post());

        if (!$product->validate()) {
            return $this->json(false, ["error" => $product->getErrors()], "Validation failed", HttpStatusCodes::BAD_REQUEST);
        }

        if (!$product->save()) {
            return $this->json(false, ["error" => $product->getErrors()], "Can't update product", HttpStatusCodes::BAD_REQUEST);
        }
        return $this->json(true, $product, "Product updated successfully", HttpStatusCodes::OK);
    }


    public function actionDelete($product_id)
    {
        $product = Product::find()->where(['id' => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], "Product not found", HttpStatusCodes::NOT_FOUND);
        }
        if ($product->delete()) {
            return $this->json(true, [], "Product deleted successfully", HttpStatusCodes::OK);
        } else {
            return $this->json(false, [], "Failed to delete product", HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
    }
}
