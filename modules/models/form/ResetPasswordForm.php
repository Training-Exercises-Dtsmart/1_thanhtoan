<?php

namespace app\modules\models\form;

use app\modules\models\User;
use Yii;
use yii\db\Exception;

class ResetPasswordForm extends User
{
    public function rules(): array
    {
        return [
            [['password_hash'], 'required'],
            [['password_hash'], 'string', 'max' => 50],
            ['password_hash', 'validatePasswordStrength'],

        ];
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public function resetPassword(): bool
    {
//        var_dump($this->password_hash);
//        die;
        $this->setPassword($this->password_hash);
        $this->password_reset_token = null;
        if ($this->save(false)) {
            return true;
        }
        return false;
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