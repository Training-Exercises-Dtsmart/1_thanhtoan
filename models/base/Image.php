<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace app\models\base;

use Yii;
use yii\helpers\ArrayHelper;
use \app\models\query\ImageQuery;

/**
 * This is the base-model class for table "image".
 *
 * @property integer $id
 * @property integer $product_id
 * @property string $name
 * @property string $path_url
 *
 * @property \app\models\Product $product
 */
abstract class Image extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'image';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $parentRules = parent::rules();
        return ArrayHelper::merge($parentRules, [
            [['product_id', 'name'], 'required'],
            [['product_id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['path_url'], 'string', 'max' => 255],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Product::class, 'targetAttribute' => ['product_id' => 'id']]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'name' => 'Name',
            'path_url' => 'Path Url',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(\app\models\Product::class, ['id' => 'product_id']);
    }

    /**
     * @inheritdoc
     * @return ImageQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ImageQuery(static::class);
    }
}
