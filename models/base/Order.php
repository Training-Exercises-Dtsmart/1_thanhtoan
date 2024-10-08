<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace app\models\base;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use \app\models\query\OrderQuery;

/**
 * This is the base-model class for table "order".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $order_code
 * @property double $total_amount
 * @property integer $status
 * @property string $shipping_address
 * @property string $customer_name
 * @property string $customer_email
 * @property string $customer_phone
 * @property string $app_trans_id
 * @property string $zp_trans_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \app\models\OrderItem[] $orderItems
 * @property \app\models\OrderPayment[] $orderPayments
 * @property \app\models\User $user
 */
abstract class Order extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order';
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
            [['user_id', 'order_code', 'total_amount', 'customer_name', 'customer_email', 'customer_phone'], 'required'],
            [['user_id', 'status'], 'integer'],
            [['total_amount'], 'number'],
            [['shipping_address'], 'string'],
            [['order_code', 'app_trans_id', 'zp_trans_id'], 'string', 'max' => 255],
            [['customer_name'], 'string', 'max' => 50],
            [['customer_email'], 'string', 'max' => 100],
            [['customer_phone'], 'string', 'max' => 20],
            [['order_code'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\User::class, 'targetAttribute' => ['user_id' => 'id']]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'id' => 'ID',
            'user_id' => 'User ID',
            'order_code' => 'Order Code',
            'total_amount' => 'Total Amount',
            'status' => 'Status',
            'shipping_address' => 'Shipping Address',
            'customer_name' => 'Customer Name',
            'customer_email' => 'Customer Email',
            'customer_phone' => 'Customer Phone',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'app_trans_id' => 'App Trans ID',
            'zp_trans_id' => 'Zp Trans ID',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(\app\models\OrderItem::class, ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPayments()
    {
        return $this->hasMany(\app\models\OrderPayment::class, ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\app\models\User::class, ['id' => 'user_id']);
    }

    /**
     * @inheritdoc
     * @return OrderQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new OrderQuery(static::class);
    }
}
