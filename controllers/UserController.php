<?php


namespace app\controllers;

use Yii;
use app\models\User;
// use yii\rest\Controller;
use app\controllers\Controller;
use app\models\form\UserForm;
use common\helpers\HttpStatusCodes;

class UserController extends Controller
{

    public function actionIndex()
    {
        $listUser = User::find()->active()->all();

        if (empty($listUser)) {
            return $this->json(false, [], "No active users found", HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listUser, "Active users retrieved successfully", HttpStatusCodes::OK);
    }

    public function actionCreate()
    {
        $user = new UserForm();
        $user->load(Yii::$app->request->post());
        if ($user->validate()) {
            if ($user->save()) {
                return $this->json(true, $user, "User created successfully", HttpStatusCodes::CREATED);
            } else {
                return $this->json(false, [], "Failed to save user", HttpStatusCodes::INTERNAL_SERVER_ERROR);
            }
        } else {
            return $this->json(false, $user->getErrors(), "Validation errors", HttpStatusCodes::BAD_REQUEST);
        }
    }

    public function actionDelete($user_id)
    {
        $user = User::findOne($user_id);

        if ($user) {
            if ($user->delete()) {
                return $this->json(true, [], 'User deleted successfully', HttpStatusCodes::OK);
            } else {
                return $this->json(false, $user->getErrors(), 'Failed to delete user', HttpStatusCodes::INTERNAL_SERVER_ERROR);
            }
        } else {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
    }

    public function actionUpdate($user_id)
    {
        $user = User::findOne($user_id);
        if (!$user) {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
        $user->load(Yii::$app->request->post());

        if ($user->save()) {
            $this->json(true, [], 'user updated successfully', HttpStatusCodes::OK);
        } else {
            return $this->json(false, $user->getErrors(), 'Failed to update the user', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
    }



    public function actionLogin()
    {
        $requestData = Yii::$app->request->post();
        if (empty($requestData['username']) || empty($requestData['password'])) {
            return $this->json(false, [], 'Empty input', HttpStatusCodes::BAD_REQUEST);
        }
        $username = 'toan';
        $passwordHash = md5('123');

        if ($requestData['username'] === $username && md5($requestData['password']) === $passwordHash) {
            return $this->json(true, [], 'Login successful', HttpStatusCodes::OK);
        } else {
            return $this->json(false, [], 'Invalid username or password', HttpStatusCodes::UNAUTHORIZED);
        }
    }
}
