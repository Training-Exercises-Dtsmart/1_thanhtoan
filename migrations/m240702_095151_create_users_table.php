<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%users}}`.
 */
class m240702_095151_create_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(50)->notNull()->unique(),
            'email' => $this->string(100)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'gender' => $this->smallInteger()->defaultValue(0),
            'first_name' => $this->string(50),
            'last_name' => $this->string(50),
            'date_of_birth' => $this->date(),
            'profile_picture' => $this->string(),
            'access_token' => $this->string(),
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
        $this->dropTable('{{%users}}');
    }
}
