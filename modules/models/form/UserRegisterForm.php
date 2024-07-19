<?php

namespace app\modules\models\form;

use Yii;
use app\modules\models\User;
use yii\db\Exception;

class UserRegisterForm extends User
{
    public $repassword;

    public function rules(): array
    {

        return array_merge(parent::rules(),
            [
                [['repassword'], 'required'],
                [['repassword'], 'string', 'max' => 255],
//                [
//                    'repassword',
//                    'compare',
//                    'compareAttribute' => 'password_hash',
//                    'message' => 'Passwords_hash don\'t match.'
//                ],
            ]

        );
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
}
