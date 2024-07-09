<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%image}}`.
 */
class m240702_095505_create_image_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->createTable('{{%image}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'name' => $this->string(100)->notNull(),
            'path_url' => $this->string(255),
        ]);

        $this->addForeignKey(
            'fk-image-product_id',
            '{{%image}}',
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
        $this->dropForeignKey('fk-image-product_id', '{{%image}}');
        $this->dropTable('{{%image}}');
    }
}
