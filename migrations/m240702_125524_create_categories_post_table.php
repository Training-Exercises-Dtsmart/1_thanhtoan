<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%categories_post}}`.
 */
class m240702_125524_create_categories_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%categories_post}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->notNull(),
            'description' => $this->string(50),
            'sethome' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%categories_post}}');
    }
}
