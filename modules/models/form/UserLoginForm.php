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
            [['username'], 'string', 'max' => 50],
            ['password_hash', 'validatePasswordStrength'],
        ];
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
