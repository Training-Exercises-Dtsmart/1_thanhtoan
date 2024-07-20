<?php

use yii\db\Migration;

/**
 * Class m240719_090529_add_password_reset_token_to_user_table
 */
class m240719_090529_add_password_reset_token_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'password_reset_token', $this->string()->unique());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'password_reset_token');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
    echo "m240719_090529_add_password_reset_token_to_user_table cannot be reverted.\n";

    return false;
    }
    */
}