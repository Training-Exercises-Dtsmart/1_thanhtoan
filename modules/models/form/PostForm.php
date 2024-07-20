<?php

namespace app\modules\models\form;

use app\modules\models\Post;

class PostForm extends Post
{
    public function rules()
    {
        return [
            [['status'], 'required'],
        ];
    }
}
