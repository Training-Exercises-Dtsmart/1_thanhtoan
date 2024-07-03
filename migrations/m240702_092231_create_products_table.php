<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%products}}`.
 */
class m240702_092231_create_products_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%products}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'name' => $this->string(50)->notNull(),
            'price' => $this->decimal(10, 2)->notNull(),
            'discount_price' => $this->decimal(10, 2)->null(),
            'stock' => $this->integer()->notNull()->defaultValue(0),
            'description' => $this->text()->null(),
            'view_count' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);
        // add foreign key for table `user`
        $this->addForeignKey(
            'fk-products-category_id',
            'products',
            'category_id',
            'categories',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-products-category_id', '{{%products}}');
        $this->dropTable('{{%products}}');
    }
}
