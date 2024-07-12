<?php

namespace app\modules\models\form;

use app\modules\models\User;
use yii\db\Exception;

class UserLoginForm extends User
{
    public function rules(): array
    {
        return [
            [['username', 'password_hash'], 'required'],
        ];
    }

}
