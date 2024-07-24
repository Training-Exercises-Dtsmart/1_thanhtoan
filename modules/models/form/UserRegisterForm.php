<?php

namespace app\modules\models\form;

use Yii;
use app\modules\models\User;
use yii\db\Exception;

class UserRegisterForm extends User
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            ['password_hash', 'validatePasswordStrength'],
            ['email', 'email'],
        ]);
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public function register(): ?UserRegisterForm
    {
        $this->status = 0;
        $this->setPassword($this->password_hash);
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
        if ($this->save()) {
            $this->sendVerificationEmail($this);
            return $this;
        }
        return null;
    }

    protected function sendVerificationEmail($user)
    {
        $verificationLink = Yii::$app->urlManager->createAbsoluteUrl([
            'api/user/verify-email',
            'token' => $user->verification_token
        ]);

        Yii::$app->queue->push(new \app\modules\jobs\SendVerificationEmailJob([
            'email' => $user->email,
            'username' => $user->username,
            'verificationLink' => $verificationLink,
        ]));
    }

    public function validatePasswordStrength($attribute): bool
    {
        if ((strlen($this->password_hash) < 6)) {
            $this->addError($attribute, 'Password must contain at least 6 characters.');
            return false;
        }
        if (!preg_match('/[A-Z]/', $this->password_hash)) {
            $this->addError($attribute, 'Password must contain at least one uppercase letter.');
            return false;
        }
        if (!preg_match('/[\W_]/', $this->password_hash)) {
            $this->addError($attribute, 'Password must contain at least one special character.');
            return false;
        }
        return true;
    }
}
