<?php

namespace app\models;

use \app\models\base\Category as BaseCategory;

/**
 * This is the model class for table "categories".
 */
class Category extends BaseCategory
{
    const STATUS_ACTIVE = 1;
    public function formName()
    {
        return "";
    }
}
