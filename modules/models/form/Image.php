<?php

namespace app\modules\models\form;

use app\models\Image as ImageModel;

//use yii\base\Model;

class Image extends ImageModel
{
    public function rules(): array
    {
        return [
            [['product_id'], 'required'],
            [['product_id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['path_url'], 'string', 'max' => 255],
        ];
    }

}