<?php

use yii\db\Migration;

/**
 * Class m240801_102953_add_app_trans_id_order_table
 */
class m240801_102953_add_app_trans_id_order_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'app_trans_id', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'app_trans_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240801_102953_add_app_trans_id_order_table cannot be reverted.\n";

        return false;
    }
    */
}
