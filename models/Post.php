<?php

namespace app\models;

use \app\models\base\Post as BasePost;
use Composer\XdebugHandler\Status;

/**
 * This is the model class for table "posts".
 */
class Post extends BasePost
{

    const STATUS_ACTIVE = 1;
    public function formName()
    {
        return '';
    }
}
