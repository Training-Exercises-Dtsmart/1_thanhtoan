<?php

namespace app\controllers;

use Yii;
// use yii\rest\Controller;
use app\controllers\Controller;
use common\helpers\HttpStatusCodes;

class WeatherController extends Controller
{
    public function actionGetWeather($city)
    {
        $weather = Yii::$app->weather->getWeather($city);
        if (!$weather) {
            return $this->json(false, [], 'Unable to retrieve weather data', HttpStatusCodes::NOT_FOUND);
        }
        return $this->json(true, $weather, 'success', HttpStatusCodes::OK);
    }
}
