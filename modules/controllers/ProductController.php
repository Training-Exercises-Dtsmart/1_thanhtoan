<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\Product;
use app\modules\models\form\ProductForm;
// use app\models\form\ProductForm;
use app\modules\models\search\ProductSearch;
use common\helpers\HttpStatusCodes;
use yii\rest\Serializer;

class ProductController extends Controller
{
    //pagination and SORT_DESC by created_at
    public function actionIndex()
    {
        $dataProvider =  Product::getAllProducts();
        if (!$dataProvider->getModels()) {
            return $this->json(false, [], "No product found", HttpStatusCodes::NOT_FOUND);
        }
        $serializer = new Serializer(['collectionEnvelope' => 'items']);
        $data = $serializer->serialize($dataProvider);
        return $this->json(true, $data, "Success", HttpStatusCodes::OK);
    }

    // search by keyword or category_name
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
        if (!$product->validate()) {
            return $this->json(false, $product->getErrors(), "Validation errors", HttpStatusCodes::BAD_REQUEST);
        }
        if (!$product->save()) {
            return $this->json(false, [], "Failed to save product", HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $product, "Product created successfully", HttpStatusCodes::CREATED);
    }
    public function actionUpdate($product_id)
    {
        $product = ProductForm::find()->where(["id" => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], 'Product not found', HttpStatusCodes::NOT_FOUND);
        }

        $product->load(Yii::$app->request->post());
        if (!$product->validate()) {
            return $this->json(false, $product->getErrors(), "Invalid product data", HttpStatusCodes::BAD_REQUEST);
        }
        if (!$product->save()) {
            return $this->json(false, $product->getErrors(), "Can't update product", HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $product, 'Update product successfully', HttpStatusCodes::OK);
    }

    public function actionDelete($product_id)
    {
        $product = Product::find()->where(["id" => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], 'Product not found', HttpStatusCodes::NOT_FOUND);
        }

        if (!$product->delete()) {
            return $this->json(false, [], 'Failed to delete product', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, [], 'Product deleted successfully', HttpStatusCodes::OK);
    }
}
