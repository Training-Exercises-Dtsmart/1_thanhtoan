<?php

namespace app\modules\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendForgotPasswordJob extends BaseObject implements JobInterface
{
    public $email;
    public $username;
    public $verificationLink;

    public function execute($queue)
    {
        Yii::$app->mailer->compose()
            ->setFrom('thanhtoan28740@gmail.com')
            ->setTo($this->email)
            ->setSubject('Email restart password')
            ->setHtmlBody("Hello {$this->username},<br>Follow the link below to restart your password:<br><a style='padding: 100px; border: 20px; background: #0a53be' href=\"{$this->verificationLink}\">Click here!</a>")
            ->send();
    }
}