<?php

namespace app\modules\models\form;

use app\modules\models\User;
use Yii;
use yii\base\Exception;

class UserUpdateProfileForm extends User
{
    public $profile_picture_file;

    public function rules(): array
    {
        return [
            [['username', 'email'], 'required'],
            [['email'], 'email'],
            [['username', 'email', 'full_name'], 'string', 'max' => 100],
            [['gender'], 'integer'],
            [['date_of_birth'], 'safe'],
            [['profile_picture'], 'string', 'max' => 255],
            [['username'], 'unique',],
            [['email'], 'unique'],
            [['profile_picture_file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],
            [
                [
                    'status',
                    'password_hash',
                    'access_token',
                    'verification_token',
                    'is_verified',
                    'status',
                    'role',
                    'password_reset_token',
                    'username',
                ],
                'validateNotChange'
            ],
        ];
    }

    public function validateNotChange($attribute)
    {
        if (!$this->isNewRecord && $this->$attribute !== $this->getOldAttribute($attribute)) {
            $this->addError($attribute, "You are not allowed to update the {$attribute} field.");
        }
    }

    /**
     * @throws Exception
     */
    public function upDateUserProfile(): bool
    {
        $user = $this;
        $user->username = $this->username;
        $user->email = $this->email;
        $user->gender = $this->gender;
        $user->full_name = $this->full_name;
        $user->date_of_birth = $this->date_of_birth;
        if ($this->profile_picture_file !== null) {
            $uploadPath = Yii::getAlias('@app/web/assets/uploads/users/');
            $fileName = Yii::$app->security->generateRandomString() . '.' . $this->profile_picture_file->extension;
            $filePath = $uploadPath . $fileName;
            if ($this->profile_picture_file->saveAs($filePath)) {
                if (!empty($user->profile_picture && file_exists($uploadPath . $user->profile_picture))) {
                    unlink($uploadPath . $user->profile_picture);
                }
                $user->profile_picture = $fileName;
            } else {
                $this->addError('profile_picture_file', 'Failed to upload the profile picture.');
                return false;
            }
        }
        return $user->save(false);
    }
}