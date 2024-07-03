<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%colors}}`.
 */
class m240702_095742_create_colors_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%colors}}', [
            'id' => $this->primaryKey(),
            'colorname' => $this->string(20)->notNull(),
            'product_id' => $this->integer()->notNull(),
        ]);


        $this->addForeignKey(
            'fk-colors-product_id',
            '{{%colors}}',
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
        $this->dropForeignKey('fk-colors-product_id', '{{%colors}}');
        $this->dropTable('{{%colors}}');
    }
}
