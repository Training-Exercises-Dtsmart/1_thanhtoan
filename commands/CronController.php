<?php

namespace app\commands;

use Yii;
use app\modules\models\User;
use yii\console\Controller;

class CronController extends Controller
{
    public function actionIndex2()
    {
        Yii::info('Hello Toan');
    }

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

        Yii::info("Emails sent successfully");
    }
}