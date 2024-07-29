<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%queue}}`.
 */
class m240729_024503_create_queue_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%queue}}', [
            'id' => $this->primaryKey(),
            'channel' => $this->string(),
            'job' => $this->binary(),
            'pushed_at' => $this->integer(),
            'ttr' => $this->integer()->unsigned(),
            'delay' => $this->integer()->unsigned(),
            'priority' => $this->integer()->unsigned(),
            'reserved_at' => $this->integer()->unsigned(),
            'attempt' => $this->integer()->unsigned(),
            'done_at' => $this->integer()->unsigned(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%queue}}');
    }
}
