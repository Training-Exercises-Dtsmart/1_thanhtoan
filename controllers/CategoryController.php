<?php

namespace app\controllers;

use Yii;
use app\models\form\CategoryForm;
use app\models\Category;
// use app\models\form\Cate;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CategoryController extends Controller
{
    public function actionIndex()
    {
        var_dump(2);
        die;
    }
    public function actionCreate()
    {
        $categoryForm = new CategoryForm();
        $categoryForm->load(Yii::$app->request->post());
        if ($categoryForm->validate()) {
            $categoryForm->save();
            return [
                'status' => true, 'now' => date('d/n/Y'), 'message' => 'Category create successfully.', 'category' => $categoryForm,
            ];
        } else {
            return $categoryForm->getErrors();
        }
    }
    public function actionDelete($categories_id)
    {

        $category = Category::find()->where(['id' => $categories_id])->one();
        if ($category === null) {
            throw new NotFoundHttpException('The requested category does not exist.');
        }
        if ($category->delete()) {
            return [
                'status' => true, 'message' => 'Category delete successfully.', 'code' => 200,
            ];
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to delete the category for unknown reasons.');
        }
    }
}
