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
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\rest\Serializer;

class ProductController extends controller
{
    public function actionIndex(): array
    {
        $dataProvider = Product::getAllProducts();
        $serializer = new Serializer(['collectionEnvelope' => 'items']);
        $data = $serializer->serialize($dataProvider);
        return $this->json(true, $data, "Success", HttpStatusCodes::OK);
    }

    public function actionSearch(): array
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if (!$dataProvider->getModels()) {
            return $this->json(false, [], "No product found", HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $dataProvider->getModels(), "Search result", HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionCreate(): array
    {
        $product = new ProductForm();
        $product->load(Yii::$app->request->post());
        if (!$product->validate()) {
            return $this->json(false, $product->getErrors(), "Validation errors", HttpStatusCodes::BAD_REQUEST);
        }
        if (!$product->save()) {
            return $this->json(false, [], "Failed to save product", HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $product, "Product created successfully", HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionUpdate($product_id): array
    {
        $product = Product::find()->where(['id' => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], "Product not found", HttpStatusCodes::NOT_FOUND);
        }
        $product->load(Yii::$app->request->post());

        if (!$product->validate()) {
            return $this->json(false, ["error" => $product->getErrors()], "Validation failed",
                HttpStatusCodes::BAD_REQUEST);
        }
        if (!$product->save()) {
            return $this->json(false, $product->getErrors(), "Can't update product",
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $product, "Product updated successfully", HttpStatusCodes::OK);
    }

    /**
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($product_id): array
    {
        $product = Product::find()->where(['id' => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], "Product not found", HttpStatusCodes::NOT_FOUND);
        }
        if (!$product->delete()) {
            return $this->json(false, [], 'Failed to delete product', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, [], "Product deleted successfully", HttpStatusCodes::OK);
    }
}
