<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%images}}`.
 */
class m240702_095505_create_images_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->createTable('{{%images}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'name' => $this->string(100)->notNull(),
            'path_url' => $this->string(255),
        ]);

        $this->addForeignKey(
            'fk-images-product_id',
            '{{%images}}',
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
        $this->dropForeignKey('fk-images-product_id', '{{%images}}');
        $this->dropTable('{{%images}}');
    }
}
