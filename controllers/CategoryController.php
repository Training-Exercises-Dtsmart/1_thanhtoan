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
    public function actionIndex(): array
    {
        $listCategory = Category::find()->all();
        if (empty($listCategory)) {
            return $this->json(false, [], "No category found", HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listCategory, "Successfully", HttpStatusCodes::OK);
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
                HttpStatusCodes::BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    public function actionUpdate($category_id): array
    {
        $category = Category::find()->where(['id' => $category_id])->one();
        if (!$category) {
            return $this->json(false, [], "Category not found", HttpStatusCodes::NOT_FOUND);
        }
        $category->load(Yii::$app->request->post());
        if (!$category->validate()) {
            return $this->json(false, ["error" => $category->getErrors()], "Validation failed",
                HttpStatusCodes::BAD_REQUEST);
        }
        if (!$category->save()) {
            return $this->json(false, $category->getErrors(), "Can't update category",
                HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $category, "Category updated successfully", HttpStatusCodes::OK);
    }


    /**
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($category_id): array
    {
        $category = Category::find()->where(['id' => $category_id])->one();
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
