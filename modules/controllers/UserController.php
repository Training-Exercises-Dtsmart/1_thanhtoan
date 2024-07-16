<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\User;
use app\modules\models\form\UserForm;
use app\modules\models\form\UserLoginForm;
use app\modules\models\form\UserRegisterForm;
use app\modules\models\form\UserUpdateForm;

use common\helpers\HttpStatusCodes;
use yii\db\Exception;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;

class UserController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login', 'register'],
        ];
        return $behaviors;
    }

    public function actionIndex(): array
    {
        $listCategories = User::find()->all();
        if (!$listCategories) {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listCategories, "success", HttpStatusCodes::OK);
    }

//    public function actionCreate(): array
//    {
//        $userForm = new UserForm();
//        $userForm->load(Yii::$app->request->post());
//        if (!$userForm->validate()) {
//            return $this->json(false, $userForm->getErrors(), 'Validation errors', HttpStatusCodes::BAD_REQUEST);
//        }
//        $user = $userForm->createUser();
//        if (!$user) {
//            return $this->json(false, [], 'Failed to create user', HttpStatusCodes::INTERNAL_SERVER_ERROR);
//        }
//        return $this->json(true, $user, 'User created successfully', HttpStatusCodes::CREATED);
//    }


    /**
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public function actionLogin(): array
    {
        $loginForm = new UserLoginForm();
        $loginForm->load(Yii::$app->request->post());
        if (!$loginForm->validate()) {
            return $this->json(false, $loginForm->getErrors(), 'Validation errors', HttpStatusCodes::BAD_REQUEST);
        }
        $user = $loginForm::findByUsername($loginForm->username);

        if (!$user) {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }

        if (!$user->validatePassword($loginForm->password_hash)) {
            return $this->json(false, $user->getErrors(), 'Password not match', HttpStatusCodes::BAD_REQUEST);
        }
        $user->generateAccessToken();
        if (!$user->save()) {
            return $this->json(false, $user->getErrors(), 'Error save', HttpStatusCodes::INTERNAL_SERVER_ERROR);
        }
        return $this->json(true, $user, 'Login successfully', HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function actionRegister(): array
    {
        $user = new UserRegisterForm();
        $user->load(Yii::$app->request->post());
        if (!$user->validate()) {
            return $this->json(false, $user->getErrors(), 'Validation errors',
                HttpStatusCodes::BAD_REQUEST);
        };
        if (!$user->register()) {
            return $this->json(false, $user->getErrors(), 'Register failed', HttpStatusCodes::BAD_REQUEST);
        }

        $auth = \Yii::$app->authManager;
        $authorRole = $auth->getRole('author');
        $auth->assign($authorRole, $user->getId());

        return $this->json(true, ['access_token' => $user->access_token, 'user' => $user], 'Register successfully',
            HttpStatusCodes::OK);
    }

    /**
     * @throws \Throwable
     */
    public function actionUpdate(): array
    {
        $user = Yii::$app->user->identity;
        $updateForm = new UserUpdateForm();
        $updateForm->load(Yii::$app->request->post());
        // Get the uploaded file instance
        $updateForm->profile_picture_file = UploadedFile::getInstance($updateForm, 'profile_picture_file');
        if (!$updateForm->validate()) {
            return $this->json(false, $updateForm->getErrors(), 'Validation errors', HttpStatusCodes::BAD_REQUEST);
        }
        if (!$updateForm->updateUser($user)) {
            return $this->json(false, $updateForm->getErrors(), 'Update failed', HttpStatusCodes::BAD_REQUEST);
        }
        return $this->json(true, $user, 'Updated successfully', HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionLogout(): array
    {
        $user = Yii::$app->user->identity;
        $user->access_token = null;

        if ($user->save(false)) {
            return $this->json(true, null, 'Logged out successfully', HttpStatusCodes::OK);
        } else {
            return $this->json(false, $user->getErrors(), 'Logout failed', HttpStatusCodes::BAD_REQUEST);
        }
    }
}
