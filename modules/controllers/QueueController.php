<?php

namespace app\modules\controllers;

use app\controllers\Controller;
use app\modules\jobs\TestQueue;

use Yii;

/**
 * Site controller
 */
class QueueController extends Controller
{

    public function actionQueue()
    {
        Yii::$app->queue->push(new TestQueue('abc'));
    }
}