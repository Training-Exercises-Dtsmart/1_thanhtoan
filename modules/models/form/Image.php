<?php

namespace app\modules\models\form;

use app\models\Image as ImageModel;

//use yii\base\Model;

class Image extends ImageModel
{
    public function rules(): array
    {

        
        return array_merge(parent::rules(), []);
    }

}