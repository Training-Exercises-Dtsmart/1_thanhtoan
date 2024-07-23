<?php

namespace app\modules\controllers;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Serializer;
use yii\web\UploadedFile;

use app\controllers\Controller;
use app\modules\models\form\ProductUpdateForm;
use app\modules\models\Product;
use app\modules\models\form\ProductForm;
use app\modules\models\form\ProductCreateForm;
use app\modules\models\form\Image;

// use app\models\form\ProductForm;
use app\modules\models\search\ProductSearch;
use common\helpers\HttpStatusCodes;

class ProductController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['index'],
        ];
        
        $behaviors['BlameableBehavior'] = [
            'class' => BlameableBehavior::class,
            'createdByAttribute' => 'user_id',
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['index'],
                    'roles' => ['?']
                ],
                [
                    'allow' => true,
                    'actions' => ['create', 'update', 'delete'],
                    'roles' => ['admin'],
                ]
            ],
        ];
        return $behaviors;
    }

    public function actionIndex(): array
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'product_all';
        // Check data cache
        $dataProvider = $cache->get($cacheKey);
        if ($dataProvider === false) {
            // No data in cache, get data from database
            $dataProvider = Product::getAllProducts();
            // assign data to cache
            $cache->set($cacheKey, $dataProvider, 3600);
        }
        if (!$dataProvider) {
            return $this->json(false, [], "No product found", HttpStatusCodes::NOT_FOUND);
        }
        $serializer = new Serializer(['collectionEnvelope' => 'items']);
        $data = $serializer->serialize($dataProvider);
        return $this->json(true, $data, 'success', HttpStatusCodes::OK);
    }

    // search by keyword or category_name
    public function actionSearch(): array
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if (!$dataProvider->getModels()) {
            return $this->json(false, [], "No product found", HttpStatusCodes::NOT_FOUND);
        }
        $serializer = new Serializer(['collectionEnvelope' => 'items']);
        $data = $serializer->serialize($dataProvider);
        return $this->json(true, $data, "Search result", HttpStatusCodes::OK);
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
        $product->user_id = Yii::$app->user->id;
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
                return $this->json(false, [], "Failed to save image", HttpStatusCodes::BAD_REQUEST);
            }
        }
        return $this->json(true, $product, "Product created successfully", HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionUpdate($product_id): array
    {
        $product = ProductUpdateForm::find()->where(["id" => $product_id])->one();
        if (!$product) {
            return $this->json(false, [], 'Product not found', HttpStatusCodes::NOT_FOUND);
        }
        $product->images = UploadedFile::getInstances($product, 'images');
        $product->load(Yii::$app->request->post());

        if (!$product->validate()) {
            return $this->json(false, $product->getErrors(), "Invalid product data", HttpStatusCodes::BAD_REQUEST);
        }
        if (!$product->save()) {
            return $this->json(false, $product->getErrors(), "Can't update product",
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        //upload multiple image
        foreach ($product->images as $imageFile) {
            $imageModel = new Image();
            $filePath = Yii::getAlias('@app/modules/uploads/products/') . $imageFile->baseName . '.' . $imageFile->extension;
            if ($imageFile->saveAs($filePath)) {
                $imageModel->product_id = $product->id;
                $imageModel->name = $imageFile->baseName . '.' . $imageFile->extension;
                $imageModel->save();
            } else {
                return $this->json(false, [], "Failed to save image", HttpStatusCodes::BAD_REQUEST);
            }
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
        //delete existing images by product
        $images = Image::find()->where(["product_id" => $product_id])->all();
        foreach ($images as $image) {
            $filePath = Yii::getAlias('@app/modules/uploads/products/') . $image->name;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            if (!$image->delete()) {
                return $this->json(false, [], "Failed to delete image", HttpStatusCodes::INTERNAL_SERVER_ERROR);
            }
        }
        if (!$product->delete()) {
            return $this->json(false, [], 'Failed to delete product', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, [], 'Product deleted successfully', HttpStatusCodes::OK);
    }
}
