<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use app\modules\models\User;

class GreetController extends Controller
{
    public function actionIndex()
    {
        $user = User::find()->where(['id' => 29])->one();
//        var_dump($user->email);
//        die;
        Yii::$app->mailer->compose()
            ->setFrom('thanhtoan28740@gmail.com')
            ->setTo($user->email)
            ->setSubject('Good Morning!')
            ->setTextBody('Good morning! Have a great day!')
            ->send();
        echo "Emails sent successfully\n";
    }
}