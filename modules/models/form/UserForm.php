<?php

namespace app\modules\models\form;

use app\models\User;
use Yii;

class UserForm extends User
{
    public $password;

    public function rules()
    {
        return array_merge(parent::rules(), [
            [['username', 'email', 'password'], 'required'],
            ['email', 'email'],
            ['password', 'string', 'min' => 6],
            [['username', 'email'], 'unique'],
        ]);
    }
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'password' => 'Password',
        ]);
    }

    public function createUser()
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        return $this->save() ? $this : null;
    }
}
