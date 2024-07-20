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
        ];
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public function resetPassword(User $user): bool
    {
        $user->setPassword($this->password_hash);
        $user->password_reset_token = null;
        if ($user->save(false)) {
            return true;
        }
        return false;
    }


}