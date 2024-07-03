<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%posts}}`.
 */
class m240702_125658_create_posts_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%posts}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(200)->notNull(),
            'content' => $this->text()->notNull(),
            'status' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);


        $this->addForeignKey(
            'fk-posts-category_id',
            '{{%posts}}',
            'category_id',
            '{{%categories}}',
            'id',
            'CASCADE'
        );


        $this->addForeignKey(
            'fk-posts-user_id',
            '{{%posts}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-posts-category_id', '{{%posts}}');
        $this->dropForeignKey('fk-posts-user_id', '{{%posts}}');
        $this->dropTable('{{%posts}}');
    }
}
