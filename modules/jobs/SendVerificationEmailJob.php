<?php

namespace app\modules\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendVerificationEmailJob extends BaseObject implements JobInterface
{
    public $email;
    public $username;
    public $verificationLink;

    public function execute($queue)
    {
        Yii::$app->mailer->compose()
            ->setFrom('thanhtoan28740@gmail.com')
            ->setTo($this->email)
            ->setSubject('Email Verification')
            ->setHtmlBody("Hello {$this->username},<br>Follow the link below to verify your email:<br><a href=\"{$this->verificationLink}\">{$this->verificationLink}</a>")
            ->send();
    }
}