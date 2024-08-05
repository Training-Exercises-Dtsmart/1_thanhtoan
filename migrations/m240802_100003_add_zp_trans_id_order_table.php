<?php

use yii\db\Migration;

/**
 * Class m240802_100003_add_zp_trans_id_order_table
 */
class m240802_100003_add_zp_trans_id_order_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'zp_trans_id', $this->string(255));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'zp_trans_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240802_100003_add_zp_trans_id_order_table cannot be reverted.\n";

        return false;
    }
    */
}
