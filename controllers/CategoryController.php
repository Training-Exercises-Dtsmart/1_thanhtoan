<?php

namespace app\controllers;

use Yii;
use app\models\form\CategoryForm;
use app\models\Category;
use app\controllers\Controller;
use common\helpers\HttpStatusCodes;

class CategoryController extends Controller
{
    public function actionIndex()
    {
        var_dump(2);
        die;
    }
    public function actionCreate()
    {
        $category = new CategoryForm();
        $category->load(Yii::$app->request->post());

        if ($category->validate()) {
            $category->save();
            return $this->json(true, $category, 'Category created successfully', HttpStatusCodes::OK);
        } else {
            return $this->json(false, $category->getErrors(), 'Validation failed', HttpStatusCodes::UNPROCESSABLE_ENTITY);
        }
    }
    public function actionDelete($categories_id)
    {

        $category = Category::find()->where(['id' => $categories_id])->one();
        if ($category === null) {
            return $this->json(false, [], 'Category not found', HttpStatusCodes::NOT_FOUND);
        }
        if ($category->delete()) {
            return $this->json(true, [], 'Category deleted successfully', HttpStatusCodes::OK);
        } else {
            return $this->json(false, [], 'Failed to delete the category', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
    }
}
