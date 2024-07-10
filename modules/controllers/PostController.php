<?php

namespace app\modules\controllers;

use Yii;
use app\models\Post;
// use
use app\modules\models\form\PostForm;
use app\controllers\Controller;
use yii\web\ServerErrorHttpException;
use common\helpers\HttpStatusCodes;

class PostController extends Controller
{
    public function actionIndex()
    {
        $listPosts = Post::find()->all();
        if (!$listPosts) {
            return $this->json(false, [], 'Post not found', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listPosts, "success", HttpStatusCodes::OK);
    }

    public function actionCreate()
    {
        $post = new PostForm();
        $post->load(Yii::$app->request->post());
        if (!$post->validate()) {
            return $this->json(false, $post->getErrors(), "Validation errors", HttpStatusCodes::BAD_REQUEST);
        }
        if (!$post->save()) {
            return $this->json(false, [], "Failed to save post", HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $post, "Post created successfully", HttpStatusCodes::OK);
    }

    public function actionUpdate($post_id)
    {
        $post = PostForm::find()->where(["id" => $post_id])->one();
        if (!$post) {
            return $this->json(false, [], 'Post not found', HttpStatusCodes::NOT_FOUND);
        }
        $post->load(Yii::$app->request->post());
        if (!$post->validate()) {
            return $this->json(false, $post->getErrors(), "Invalid post data", HttpStatusCodes::BAD_REQUEST);
        }
        if (!$post->save()) {
            return $this->json(false, $post->getErrors(), "Can't update post", HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $post, 'Update post successfully', HttpStatusCodes::OK);
    }

    public function actionDelete($post_id)
    {
        $post = Post::find()->where(["id" => $post_id])->one();
        if (!$post) {
            return $this->json(false, [], 'Post not found', HttpStatusCodes::NOT_FOUND);
        }

        if (!$post->delete()) {
            return $this->json(false, [], 'Failed to delete post', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, [], 'Post deleted successfully', HttpStatusCodes::OK);
    }
}