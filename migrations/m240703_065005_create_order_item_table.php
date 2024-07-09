<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order_item}}`.
 */
class m240703_065005_create_order_item_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%order_item}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'quantity' => $this->integer()->unsigned()->notNull(),
            'price' => $this->double()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-order_item-order_id',
            '{{%order_item}}',
            'order_id',
            '{{%order}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-order_item-product_id',
            '{{%order_item}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-order_item-order_id', '{{%order_item}}');

        $this->dropForeignKey('fk-order_item-product_id', '{{%order_item}}');

        $this->dropTable('{{%order_item}}');
    }
}
