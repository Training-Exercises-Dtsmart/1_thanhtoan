<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order}}`.
 */
class m240702_131434_create_order_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%order}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'order_code' => $this->string()->notNull()->unique(),
            'total_amount' => $this->double()->notNull(),
            'status' => $this->smallInteger()->defaultValue(0),
            'shipping_address' => $this->text(),
            'billing_address' => $this->text(),
            'customer_name' => $this->string(50)->notNull(),
            'customer_email' => $this->string(100)->notNull(),
            'customer_phone' => $this->string(20)->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
            'fk-order-user_id',
            '{{%order}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-order-user_id', '{{%order}}');
        $this->dropTable('{{%order}}');
    }
}
