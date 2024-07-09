<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%category_post}}`.
 */
class m240702_125524_create_category_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%category_post}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(50)->notNull(),
            'description' => $this->string(50),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->addForeignKey(
            'fk-category_post-user_id',
            '{{%category_post}}',
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
        $this->dropForeignKey('fk-category_post-user_id', '{{%category_post}}');
        $this->dropTable('{{%category_post}}');
    }
}
