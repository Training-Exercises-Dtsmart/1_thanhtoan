<?php

namespace app\modules\models\form;

use yii;
use app\modules\models\User;

class UserUpdateForm extends User
{
    /**
     * @var mixed|yii\web\UploadedFile|null
     */
    public $profile_picture_file;

    public function rules(): array
    {
        return [
            [['email'], 'string', 'max' => 255],
            [['gender'], 'integer'],
            [['full_name'], 'string', 'max' => 255],
            [['profile_picture'], 'string', 'max' => 255],
            [['date_of_birth'], 'date', 'format' => 'php:Y-m-d'],
            [['profile_picture'], 'string', 'max' => 255],
            [['profile_picture_file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],
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
        if ($this->profile_picture_file !== null) {
            $filePath = Yii::getAlias('@app/modules/uploads/') . $this->profile_picture_file->baseName . '.' . $this->profile_picture_file->extension;
            if ($this->profile_picture_file->saveAs($filePath)) {
                $user->profile_picture = $this->profile_picture_file->baseName . '.' . $this->profile_picture_file->extension;
            }
        }
        if ($user->save()) {
            return $user;
        }
        return null;
    }


}
