<?php

namespace app\modules\models\form;

use Yii;
use app\modules\models\User;
use yii\base\Exception;
use app\modules\jobs\SendForgotPasswordJob;

class UserSendMailForgotPasswordForm extends User
{
    public function rules(): array
    {
        return [
            [['email'], 'required'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 100],
        ];
    }

    /**
     * @throws Exception
     */
    public function sendEmailResetPassword(): bool
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
        if ($this->save()) {
            $verificationLink = Yii::$app->urlManager->createAbsoluteUrl([
                'api/user/change-password',
                'token' => $this->password_reset_token
            ]);
            Yii::$app->queue->push(new SendForgotPasswordJob([
                'email' => $this->email,
                'username' => $this->username,
                'verificationLink' => $verificationLink,
            ]));
            return true;
        }
        return false;
    }
}