<?php

namespace app\controllers;

use Yii;
use app\models\form\CategoryForm;
use app\models\Category;
use app\controllers\Controller;
use common\helpers\HttpStatusCodes;
use yii\db\Exception;
use yii\db\StaleObjectException;

class CategoryController extends Controller
{
    public function actionIndex()
    {
        var_dump(2);
        die;
    }

    /**
     * @throws Exception
     */
    public function actionCreate(): array
    {
        $category = new CategoryForm();
        $category->load(Yii::$app->request->post());

        if ($category->validate()) {
            $category->save();
            return $this->json(true, $category, 'Category created successfully', HttpStatusCodes::OK);
        } else {
            return $this->json(false, $category->getErrors(), 'Validation failed',
                HttpStatusCodes::UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($categories_id): array
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
