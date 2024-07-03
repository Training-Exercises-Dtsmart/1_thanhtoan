<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%orders}}`.
 */
class m240702_131434_create_orders_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%orders}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'order_number' => $this->string()->notNull()->unique(),
            'total_amount' => $this->decimal(10, 2)->notNull(),
            'status' => $this->smallInteger()->defaultValue(0),
            'shipping_address' => $this->text(),
            'billing_address' => $this->text(),
            'payment_method' => $this->string(50)->notNull(),
            'customer_name' => $this->string(50)->notNull(),
            'customer_email' => $this->string(100)->notNull(),
            'customer_phone' => $this->string(20)->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
            'fk-orders-user_id',
            '{{%orders}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-orders-user_id', '{{%orders}}');
        $this->dropTable('{{%orders}}');
    }
}
