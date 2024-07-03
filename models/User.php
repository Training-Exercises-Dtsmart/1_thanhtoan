<?php

namespace app\models;

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
}
