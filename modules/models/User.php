<?php

namespace app\modules\models;

use Yii;
use yii\base\Exception;
use yii\filters\RateLimitInterface;
use yii\web\IdentityInterface;
use app\models\User as BaseUser;

class User extends BaseUser implements IdentityInterface, RateLimitInterface
{
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['password_hash']);
        unset($fields['verification_token']);
        return $fields;
    }

    public function getRateLimit($request, $action): array
    {
        return [30, 60]; // Limit 50 request in 60s
    }

    public function loadAllowance($request, $action): array
    {
        return [$this->allowance, $this->allowance_updated_at];
    }

    /**
     * @throws \yii\db\Exception
     */
    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $this->allowance = $allowance;
        $this->allowance_updated_at = $timestamp;
        $this->save();
    }


    public static function findIdentity($id): ?User
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?User
    {
        return static::findOne(['access_token' => $token]);
    }

    public static function findByUsername($username): ?User
    {
        return static::findOne(['username' => $username]);
    }

    public function validatePassword($password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * @throws Exception
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * @throws Exception
     */
    public function generateAccessToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString();
    }

    /**
     * @throws Exception
     */
    public function generateVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }


    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
    }

    public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
    }
}
