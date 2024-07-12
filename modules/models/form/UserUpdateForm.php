<?php

namespace app\modules\models\form;

use yii;
use app\modules\models\User;

class UserUpdateForm extends User
{
    public function rules(): array
    {
        return [
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['date_of_birth'], 'date', 'format' => 'php:Y-m-d'],
            [
                ['email'],
                'unique',
                'targetClass' => '\app\modules\models\User',
                'filter' => function ($query) {
                    $query->andWhere(['<>', 'id', Yii::$app->user->identity->id]);
                }
            ],
        ];
    }

    public function updateUser($user)
    {

        if ($this->email !== null) {
            $user->email = $this->email;
        }
        if ($this->gender !== null) {
            $user->gender = $this->gender;
        }
        if ($this->full_name !== null) {
            $user->full_name = $this->full_name;
        }
        if ($this->date_of_birth !== null) {
            $user->date_of_birth = $this->date_of_birth;
        }
        if ($this->profile_picture !== null) {
            $user->profile_picture = $this->profile_picture;
        }

        if ($user->save()) {
            return $user;
        }
        return null;
    }


}
