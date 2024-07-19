<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\User;
use app\modules\models\form\UserLoginForm;
use app\modules\models\form\UserRegisterForm;
use app\modules\models\form\UserUpdateForm;

use common\helpers\HttpStatusCodes;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;

class UserController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login', 'register', 'verify-email'],
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['login', 'register', 'verify-email'],
                    'roles' => ['?']
                ],
                [
                    'allow' => true,
                    'actions' => ['update'],
                    'roles' => ['author'],
                ],
                [
                    'allow' => true,
                    'actions' => ['delete', 'index'],
                    'roles' => ['admin'],
                ],
            ],
        ];
        return $behaviors;
    }

    public function actionIndex(): array
    {
        $listUser = User::find()->all();
        if (!$listUser) {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $listUser, "success", HttpStatusCodes::OK);
    }

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
        if ($user->is_verified === 0) {
            return $this->json(false, $user->getErrors(), 'User is not verified', HttpStatusCodes::NOT_FOUND);
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
        return $this->json(true, ['user' => $user], 'Register successfully',
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

    /**
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($user_id): array
    {
        $user = User::find()->where(['id' => $user_id])->one();
        if (!$user) {
            return $this->json(true, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
        if (!$user->delete()) {
            return $this->json(false, $user->getErrors(), 'Delete failed', HttpStatusCodes::BAD_REQUEST);
        }
        return $this->json(true, [], 'Deleted user successfully', HttpStatusCodes::OK);
    }

    /**
     * @throws Exception
     */
    public function actionVerifyEmail($token): array
    {
        $user = User::find()->where(['verification_token' => $token])->one();
        if (!$user) {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
        $user->is_verified = 1;
        $user->verification_token = null;
        if ($user->save(false)) {
            return $this->json(true, [], 'Your email has been verified successfully.', HttpStatusCodes::OK);
        } else {
            return $this->json(false, null, 'The verification link is invalid or expired.',
                HttpStatusCodes::BAD_REQUEST);
        }
    }
}
