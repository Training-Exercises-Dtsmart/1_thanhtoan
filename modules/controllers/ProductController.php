<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\Product;
use app\modules\models\form\ProductForm;
use app\modules\models\form\ProductCreateForm;
use app\modules\models\form\Image;

// use app\models\form\ProductForm;
use app\modules\models\search\ProductSearch;
use common\helpers\HttpStatusCodes;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\rest\Serializer;
use yii\web\UploadedFile;

class ProductController extends Controller
{
    public function actionIndex(): array
    {
        $dataProvider = Product::getAllProducts();
        if (!$dataProvider->getModels()) {
            return $this->json(false, [], "No product found", HttpStatusCodes::NOT_FOUND);
        }
        $serializer = new Serializer(['collectionEnvelope' => 'items']);
        $data = $serializer->serialize($dataProvider);
        return $this->json(true, $data, "Success", HttpStatusCodes::OK);
    }

    // search by keyword or category_name
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
        $product = new ProductCreateForm();
        $product->load(Yii::$app->request->post());
        $product->images = UploadedFile::getInstances($product, 'images');

        if (!$product->validate()) {
            return $this->json(false, $product->getErrors(), "Validation errors", HttpStatusCodes::BAD_REQUEST);
        }
        if (!$product->save()) {
            return $this->json(false, [], "Failed to save product", HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        
        //upload multiple image
        foreach ($product->images as $imageFile) {
            $imageModel = new Image();
            $filePath = Yii::getAlias('@app/modules/uploads/products/') . $imageFile->baseName . '.' . $imageFile->extension;
            if ($imageFile->saveAs($filePath)) {
                $imageModel->product_id = $product->id;
                $imageModel->name = $imageFile->baseName . '.' . $imageFile->extension;
//                $imageModel->path_url = $filePath;
                $imageModel->save();
            } else {
                return $this->json(false, [], "Failed to save image", HttpStatusCodes::INTERNAL_SERVER_ERROR);
            }
        }
        return $this->json(true, $product, "Product created successfully", HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionUpdate($product_id): array
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
            return $this->json(false, $product->getErrors(), "Can't update product",
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $product, 'Update product successfully', HttpStatusCodes::OK);
    }

    /**
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($product_id): array
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
