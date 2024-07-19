<?php

namespace app\modules\controllers;

use Yii;
use app\modules\models\Post;
use app\modules\models\form\PostForm;
use app\controllers\Controller;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use common\helpers\HttpStatusCodes;

class PostController extends Controller
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
                ],
            ],
        ];

        return $behaviors;
    }


    public function actionIndex(): array
    {
        $listPosts = Post::find()->all();
        if (!$listPosts) {
            return $this->json(false, [], 'Post not found', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listPosts, "success", HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionCreate(): array
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

    /**
     * @throws Exception
     */
    public function actionUpdate($post_id): array
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

    /**
     * @throws StaleObjectException
     * @throws \Throwable
     */

    public function actionDelete($post_id): array
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