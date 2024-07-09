<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order_payment}}`.
 */
class m240709_085622_create_order_payment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%order_payment}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'payment_method' => $this->string(50)->notNull(),
            'amount' => $this->double()->notNull(),
            'transaction_id' => $this->string(100)->notNull(),
            'status' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
            'fk-order_payment-order_id',
            '{{%order_payment}}',
            'order_id',
            '{{%order}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-order_payment-order_id', '{{%order_payment}}');
        $this->dropTable('{{%order_payment}}');
    }
}
