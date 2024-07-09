<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post}}`.
 */
class m240702_125658_create_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%post}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(200)->notNull(),
            'content' => $this->text()->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);


        $this->addForeignKey(
            'fk-post-category_id',
            '{{%post}}',
            'category_id',
            '{{%category_post}}',
            'id',
            'CASCADE'
        );


        $this->addForeignKey(
            'fk-post-user_id',
            '{{%post}}',
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
        $this->dropForeignKey('fk-post-category_id', '{{%post}}');
        $this->dropForeignKey('fk-post-user_id', '{{%post}}');
        $this->dropTable('{{%post}}');
    }
}
