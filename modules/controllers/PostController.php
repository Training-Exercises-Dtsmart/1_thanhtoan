<?php

namespace app\modules\controllers;

use Yii;
use app\models\Post;
// use
use app\modules\models\form\PostForm;
use app\controllers\Controller;
use yii\web\ServerErrorHttpException;

class PostController extends Controller
{
    public function actionIndex()
    {
        $postlist = Post::find()->all();
        return $postlist;
    }

    public function actionCreate()
    {
        $postForm = new PostForm();
        $postForm->load(Yii::$app->request->post());
        if (!$postForm->validate() || !$postForm->save()) {
            return $this->json(false, [
                "errors" => $postForm->getErrors()
            ], "Can't update Post", 400);
        }
        return $this->json(true, $postForm, "Success");
    }

    public function actionUpdate($post_id)
    {
        $post = Post::find()->where(["id" => $post_id])->one();
        if (!$post) {
            return $this->json(false, [], "Product not found", 404);
        }
        $post->load(Yii::$app->request->post());
        if (!$post->validate() || !$post->save()) {
            $this->json(false, [], "Can't update product", 400);
        }
        return $this->json(true, $post, "update product successfully");
    }

    public function actionDelete($post_id)
    {
        $post = Post::find()->where(["id" => $post_id])->one();
        if (!$post) {
            return  $this->json(false, [], "Product not found", 404);
        }

        if ($post->delete()) {
            return $this->json(true, [], "Product deleted successfully", 200);
        } else {
            throw new ServerErrorHttpException('Failed to delete product');
        }
    }
}
