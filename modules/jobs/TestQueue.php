<?php

namespace app\modules\jobs;

class TestQueue extends \yii\base\BaseObject implements \yii\queue\RetryableJobInterface
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function execute($queue)
    {
        error_log('My name is: ' . $this->name);
    }

    public function getTtr(): int
    {
        return 60;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3;
    }
}