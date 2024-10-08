<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace app\models\base;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use \app\models\query\OrderPaymentQuery;

/**
 * This is the base-model class for table "order_payment".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $payment_method
 * @property double $amount
 * @property string $transaction_id
 * @property integer $status
 * @property string $m_refund_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \app\models\Order $order
 */
abstract class OrderPayment extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_payment';
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
            [['order_id', 'payment_method', 'amount', 'transaction_id'], 'required'],
            [['order_id', 'status'], 'integer'],
            [['amount'], 'number'],
            [['payment_method'], 'string', 'max' => 50],
            [['transaction_id'], 'string', 'max' => 100],
            [['m_refund_id'], 'string', 'max' => 255],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Order::class, 'targetAttribute' => ['order_id' => 'id']]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'payment_method' => 'Payment Method',
            'amount' => 'Amount',
            'transaction_id' => 'Transaction ID',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'm_refund_id' => 'M Refund ID',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(\app\models\Order::class, ['id' => 'order_id']);
    }

    /**
     * @inheritdoc
     * @return OrderPaymentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new OrderPaymentQuery(static::class);
    }
}
