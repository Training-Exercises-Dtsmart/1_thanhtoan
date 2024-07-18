<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;

/**
 * Site controller
 */
class SendMailController extends Controller
{
    //...............
    public function actionSendmail()
    {
        //send two mail,..
//        $messages = [];
//        $users = [
//            'toan70868@gmail.com',
//            'kissuot6@gmail.com',
//        ];
//
//        foreach ($users as $user) {
//
//            $messages[] = Yii::$app->mailer->compose()
//                ->setFrom('thanhtoan28740@gmail.com')
//                ->setTo($user)
//                ->setSubject('Demo gửi multi mail trong Yii2')
//                ->setHtmlBody('<b>Nội dung gửi multi mail trong Yii2</b>');
//        }
//        Yii::$app->mailer->sendMultiple($messages);


        //Send one mail
        Yii::$app->mailer->compose() // Sử dụng nếu có template
        ->setFrom('thanhtoan28740@gmail.com') // Mail sẽ gửi đi
        ->setTo('kissuot6@gmail.com') // Mail sẽ nhận
        ->setSubject('Em Tòn nè anh ơi :>>') // tiêu đề mail
        ->setHtmlBody('<b>Long time no see!!!</b>') // Nội dung mail dạng Html nếu không muốn dùng html thì có thể thay thế bằng setTextBody('Nội dung gửi mail trong Yii2') để chỉ hiển thị text
        ->send();
    }
}