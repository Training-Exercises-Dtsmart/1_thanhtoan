<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\User;
use app\modules\models\form\UserLoginForm;
use app\modules\models\form\UserRegisterForm;
use app\modules\models\form\UserUpdateForm;
use app\modules\models\form\UserSendMailForgotPasswordForm;
use app\modules\models\form\ResetPasswordForm;
use app\modules\models\form\UserUpdateProfileForm;
use common\helpers\HttpStatusCodes;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\RateLimiter;
use yii\web\UploadedFile;

class UserController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => [
                'login',
                'register',
                'verify-email',
                'forgot-password',
                'reset-password',
                'change-password-forgot'
            ],
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => [
                        'login',
                        'register',
                        'verify-email',
                        'forgot-password',
                        'reset-password',
                        'change-password-forgot'
                    ],
                    'roles' => ['?']
                ],
                [
                    'allow' => true,
                    'actions' => ['update-profile', 'logout'],
                    'roles' => ['author'],
                ],
                [
                    'allow' => true,
                    'actions' => ['delete', 'index', 'update-user'],
                    'roles' => ['admin'],
                ],
            ],
        ];

        $behaviors['rateLimiter'] = [
            'class' => RateLimiter::class,
            'enableRateLimitHeaders' => true,
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
    public function actionUpdateProfile(): array
    {
        $user = Yii::$app->user->identity;
        $updateForm = UserUpdateProfileForm::find()->where(['id' => $user->id])->one();
        $updateForm->profile_picture_file = UploadedFile::getInstance($updateForm, 'profile_picture_file');
        $updateForm->load(Yii::$app->request->post());
        if (!$updateForm->validate()) {
            return $this->json(false, $updateForm->getErrors(), 'Validation errors', HttpStatusCodes::BAD_REQUEST);
        }
        if (!$updateForm->upDateUserProfile()) {
            return $this->json(false, $updateForm->getErrors(), 'Update failed', HttpStatusCodes::BAD_REQUEST);
        }
        return $this->json(true, $updateForm, 'Updated successfully', HttpStatusCodes::OK);
    }

    public function actionUpdateUser(): array
    {
        $user = Yii::$app->user->identity;
        $updateForm = UserUpdateForm::find()->where(['id' => $user->id])->one();
        $updateForm->load(Yii::$app->request->post());
        $updateForm->profile_picture_file = UploadedFile::getInstance($updateForm, 'profile_picture_file');
        if (!$updateForm->validate()) {
            return $this->json(false, $updateForm->getErrors(), 'Validation errors', HttpStatusCodes::BAD_REQUEST);
        }
        if (!$updateForm->updateUser()) {
            return $this->json(false, $updateForm->getErrors(), 'Update failed', HttpStatusCodes::BAD_REQUEST);
        }
        return $this->json(true, $updateForm, 'Updated successfully', HttpStatusCodes::OK);
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

    /**
     * @throws \yii\base\Exception
     */
    public function actionForgotPassword(): array
    {
        $userForm = new UserSendMailForgotPasswordForm();
        $userForm->email = Yii::$app->request->post('email');
        if (!$userForm->validate()) {
            return $this->json(false, $userForm->getErrors(), 'Validation failed', HttpStatusCodes::BAD_REQUEST);
        }
        $user = UserSendMailForgotPasswordForm::find()->where(['email' => $userForm->email])->one();
        if (!$user) {
            return $this->json(false, [], 'User not found', HttpStatusCodes::NOT_FOUND);
        }
        if (!$user->sendEmailResetPassword()) {
            return $this->json(false, $user->getErrors(), 'Send email forgot password errors',
                HttpStatusCodes::BAD_REQUEST);
        }
        return $this->json(true, $user, 'Send email forgot password successfully', HttpStatusCodes::OK);
    }

    /**
     * @throws \yii\base\Exception
     * @throws Exception
     */
    public function actionChangePasswordForgot($token): array
    {
        $user = ResetPasswordForm::find()->where(['password_reset_token' => $token])->one();
        if (!$user) {
            return $this->json(false, [], 'URL not found', HttpStatusCodes::NOT_FOUND);
        }
        $user->load(Yii::$app->request->post());
        if (!$user->validate()) {
            return $this->json(false, $user->getErrors(), 'Validation errors', HttpStatusCodes::BAD_REQUEST);
        }
        if ($user->resetPassword()) {
            return $this->json(true, [], 'Change password successfully', HttpStatusCodes::OK);
        }
        return $this->json(false, $user->getErrors(), 'Change password errors', HttpStatusCodes::BAD_REQUEST);
    }

}
