<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%orders_item}}`.
 */
class m240703_065005_create_orders_item_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%orders_item}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'quantity' => $this->integer()->notNull(),
            'price' => $this->decimal(10, 2)->notNull(),
            'total_price' => $this->decimal(10, 2)->notNull(),
        ]);

        $this->addForeignKey(
            'fk-orders_item-order_id',
            '{{%orders_item}}',
            'order_id',
            '{{%orders}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-orders_item-product_id',
            '{{%orders_item}}',
            'product_id',
            '{{%products}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-orders_item-order_id', '{{%orders_item}}');

        $this->dropForeignKey('fk-orders_item-product_id', '{{%orders_item}}');

        $this->dropTable('{{%orders_item}}');
    }
}
