<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\User;
use app\modules\models\form\UserForm;
use common\helpers\HttpStatusCodes;

class UserController extends Controller
{
    public function actionIndex()
    {
        $listCategories = User::find()->all();
        if (!$listCategories) {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listCategories, "success", HttpStatusCodes::OK);
    }
    public function actionCreate()
    {
        $userForm  = new UserForm();
        $userForm->load(Yii::$app->request->post());
        if (!$userForm->validate()) {
            return $this->json(false, $userForm->getErrors(), 'Validation errors', HttpStatusCodes::BAD_REQUEST);
        }
        $user = $userForm->createUser();
        if (!$user) {
            return $this->json(false, [], 'Failed to create user', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $user, 'User created successfully', HttpStatusCodes::CREATED);
    }
}
