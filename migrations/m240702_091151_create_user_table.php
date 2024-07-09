<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user}}`.
 */
class m240702_091151_create_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(50)->notNull()->unique(),
            'email' => $this->string(100)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'gender' => $this->smallInteger()->defaultValue(0),
            'full_name' => $this->string(100),
            'date_of_birth' => $this->date(),
            'profile_picture' => $this->string(),
            'access_token' => $this->string(),
            'is_verified' => $this->boolean()->defaultValue(false),
            'status' => $this->smallInteger()->defaultValue(0),
            'role' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}
