<?php

namespace app\modules\controllers;

use app\controllers\Controller;
use app\models\form\CategoryForm;
use app\modules\models\Category;
use common\helpers\HttpStatusCodes;
use Yii;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;

class CategoryController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['index'],
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['create', 'update', 'delete'],
                    'roles' => ['admin'],
                ],
                [
                    'allow' => true,
                    'actions' => ['index'],
                    'roles' => ['?'],
                ]
            ],
        ];
        return $behaviors;
    }

    public function actionIndex(): array
    {
        $listCategory = Category::find()->where(['status' => Category::activeCategory])->all();
        if (!$listCategory) {
            return $this->json(false, [], 'Category not found', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listCategory, 'Success', HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionCreate(): array
    {
        $category = new CategoryForm();
        $category->load(Yii::$app->request->post());
        if (!$category->validate()) {
            return $this->json(false, $category->getErrors(), HttpStatusCodes::BAD_REQUEST);
        }
        if ($category->save()) {
            return $this->json(true, $category, 'Category created successfully', HttpStatusCodes::OK);
        }
        return $this->json(false, $category->getErrors(), HttpStatusCodes::BAD_REQUEST);
    }

    /**
     * @throws Exception
     */
    public function actionUpdate($category_id): array
    {
        $category = CategoryForm::find()->where(['id' => $category_id])->one();
        if (!$category) {
            return $this->json(false, [], 'Category not found', HttpStatusCodes::NOT_FOUND);
        }
        $category->load(Yii::$app->request->post());
        if (!$category->validate()) {
            return $this->json(false, $category->getErrors(), HttpStatusCodes::BAD_REQUEST);
        }
        if ($category->save()) {
            return $this->json(true, $category, 'Category updated successfully', HttpStatusCodes::OK);
        }
        return $this->json(false, $category->getErrors(), HttpStatusCodes::BAD_REQUEST);
    }

    /**
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($category_id): array
    {
        $category = Category::find()->where(['id' => $category_id])->one();
        if (!$category) {
            return $this->json(false, [], 'Category not found', HttpStatusCodes::NOT_FOUND);
        }
        if ($category->delete()) {
            return $this->json(true, [], 'Category deleted successfully', HttpStatusCodes::OK);
        }
        return $this->json(false, $category->getErrors(), 'Failed to delete category', HttpStatusCodes::BAD_REQUEST);
    }


}