<?php


namespace app\models;

use Yii;

use \app\models\base\User as BaseUser;

/**
 * This is the model class for table "users".
 */
class User extends BaseUser
{
    const STATUS_ACTIVE = 1;

    public function formName()
    {
        return '';
    }

    public static function findActive()
    {
        return self::find()->where(['status' => 1])->all();
    }

    public static function findOneUser($user_id): ?User
    {
        return self::findOne($user_id);
    }

    public static function findRecent($days = 7): array
    {
        $time = new \DateTime();
        $time->modify("-{$days} days");
        return self::find()->where(['>=', 'created_at', $time->format('Y-m-d H:i:s')])->all();
    }
}
