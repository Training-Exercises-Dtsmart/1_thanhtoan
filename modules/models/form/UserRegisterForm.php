<?php

namespace app\modules\models\form;

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
        $this->setPassword($this->password_hash);
        $this->generateAccessToken();
        if ($this->save()) {
            return $this;
        }
        return null;
    }
}
