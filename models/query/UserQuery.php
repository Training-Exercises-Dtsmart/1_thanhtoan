<?php

namespace app\models\query;

use \app\models\User;

/**
 * This is the ActiveQuery class for [[User]].
 *
 * @see \app\models\User
 * @method User[] all($db = null)
 * @method User one($db = null)
 */
class UserQuery extends \yii\db\ActiveQuery
{

    public function active()
    {
        return $this->andwhere(['status' => '1']);
    }
}
