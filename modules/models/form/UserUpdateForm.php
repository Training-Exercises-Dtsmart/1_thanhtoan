<?php

namespace app\modules\models\form;

use yii;
use app\modules\models\User;
use yii\base\Exception;

class UserUpdateForm extends User
{
    /**
     * @var mixed|yii\web\UploadedFile|null
     */
    public $profile_picture_file;

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['profile_picture_file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],
            [
                [
                    'password_hash',
                    'access_token',
                    'verification_token',
                    'is_verified',
                    'password_reset_token',
                ],
                'validateNotChange'
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateUser(): bool
    {
        $user = $this;
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

    public function validateNotChange($attribute)
    {
        if (!$this->isNewRecord && $this->$attribute !== $this->getOldAttribute($attribute)) {
            $this->addError($attribute, "You are not allowed to update the {$attribute} field.");
        }
    }


}
