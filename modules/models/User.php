<?php

namespace app\modules\models;

use app\models\User as BaseUser;

class User extends BaseUser
{
    public function fields()
    {
        // return array(parent::fields(), 'name', 'description', 'price');
        return array_merge(parent::fields(), []);
    }
}
