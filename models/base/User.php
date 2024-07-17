<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace app\models\base;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use \app\models\query\UserQuery;

/**
 * This is the base-model class for table "user".
 *
 * @property integer $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property integer $gender
 * @property string $full_name
 * @property string $date_of_birth
 * @property string $profile_picture
 * @property string $access_token
 * @property string $verification_token
 * @property integer $is_verified
 * @property integer $status
 * @property integer $role
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \app\models\Category[] $categories
 * @property \app\models\CategoryPost[] $categoryPosts
 * @property \app\models\Order[] $orders
 * @property \app\models\Post[] $posts
 * @property \app\models\Product[] $products
 */
abstract class User extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['timestamp'] = [
            'class' => TimestampBehavior::class,
            'value' => (new \DateTime())->format('Y-m-d H:i:s'),
                        ];
        
    return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $parentRules = parent::rules();
        return ArrayHelper::merge($parentRules, [
            [['username', 'email', 'password_hash'], 'required'],
            [['gender', 'is_verified', 'status', 'role'], 'integer'],
            [['date_of_birth'], 'safe'],
            [['username'], 'string', 'max' => 50],
            [['email', 'full_name'], 'string', 'max' => 100],
            [['password_hash', 'profile_picture', 'access_token', 'verification_token'], 'string', 'max' => 255],
            [['username'], 'unique'],
            [['email'], 'unique']
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'password_hash' => 'Password Hash',
            'gender' => 'Gender',
            'full_name' => 'Full Name',
            'date_of_birth' => 'Date Of Birth',
            'profile_picture' => 'Profile Picture',
            'access_token' => 'Access Token',
            'verification_token' => 'Verification Token',
            'is_verified' => 'Is Verified',
            'status' => 'Status',
            'role' => 'Role',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(\app\models\Category::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategoryPosts()
    {
        return $this->hasMany(\app\models\CategoryPost::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(\app\models\Order::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosts()
    {
        return $this->hasMany(\app\models\Post::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(\app\models\Product::class, ['user_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return UserQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserQuery(static::class);
    }
}
