<?php

use yii\db\Migration;

/**
 * Class m240726_012854_update_order_and_order_item_tables
 */
class m240726_012854_update_order_and_order_item_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%order}}', 'billing_address');

        $this->addColumn('{{%order_item}}', 'name', $this->string());
        $this->addColumn('{{%order_item}}', 'created_at', $this->dateTime());
        $this->addColumn('{{%order_item}}', 'updated_at', $this->dateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{%order}}', 'billing_address', $this->string()->notNull());
        $this->dropColumn('{{%order_item}}', 'name');
        $this->dropColumn('{{%order_item}}', 'created_at');
        $this->dropColumn('{{%order_item}}', 'updated_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
    
    }
    
    public function down()
    {
    echo "m240726_012854_update_order_and_order_item_tables cannot be reverted.\n";
    
    return false;
    }
    */
}