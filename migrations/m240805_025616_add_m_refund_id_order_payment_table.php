<?php

use yii\db\Migration;

/**
 * Class m240805_025616_add_m_refund_id_order_payment_table
 */
class m240805_025616_add_m_refund_id_order_payment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order_payment}}', 'm_refund_id', $this->string(255));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%order_payment}}', 'm_refund_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240805_025616_add_m_refund_id_order_payment_table cannot be reverted.\n";

        return false;
    }
    */
}
