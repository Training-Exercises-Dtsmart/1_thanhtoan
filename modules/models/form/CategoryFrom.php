<?php

namespace app\modules\models\form;

use app\modules\models\Category;

class CategoryFrom extends Category
{
    public function rules(): array
    {
        return array_merge(parent::rules(), []);
    }

}