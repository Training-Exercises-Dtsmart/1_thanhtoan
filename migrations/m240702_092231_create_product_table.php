<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product}}`.
 */
class m240702_092231_create_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(50)->notNull(),
            'price' => $this->double()->notNull(),
            'discount_price' => $this->double()->null(),
            'stock' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'description' => $this->text()->null(),
            'view_count' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
            'fk-product-category_id',
            'product',
            'category_id',
            'category',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-product-user_id',
            'product',
            'user_id',
            'user',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-product-category_id', '{{%product}}');
        $this->dropForeignKey('fk-product-user_id', '{{%product}}');
        $this->dropTable('{{%product}}');
    }
}
