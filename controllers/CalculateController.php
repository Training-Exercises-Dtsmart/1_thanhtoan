<?php


namespace app\controllers;

use Yii;
// use yii\rest\Controller;
use app\controllers\Controller;
use common\helpers\HttpStatusCodes;

class CalculateController extends Controller
{

    public function actionTotal()
    {
        $dataRequest = Yii::$app->request->post();

        if (!empty($dataRequest['soa']) && !empty($dataRequest['sob'])) {
            if (is_numeric($dataRequest['soa']) && is_numeric($dataRequest['sob'])) {
                $total = $dataRequest['soa'] + $dataRequest['sob'];
                return $this->json(true, $total, 'Calculate total successfully', HttpStatusCodes::OK);
            } else {
                return $this->json(false, [], 'Values must be numeric', HttpStatusCodes::UNPROCESSABLE_ENTITY);
            }
        } else {
            return $this->json(false, [], 'Please provide soa and sob', HttpStatusCodes::BAD_REQUEST);
        }
    }

    public function actionDivide()
    {
        $dataRequest = Yii::$app->request->post();
        if (!empty($dataRequest['soa']) && !empty($dataRequest['sob'])) {
            if (is_numeric($dataRequest['soa']) && is_numeric($dataRequest['sob'])) {
                $totalDevide = $dataRequest['soa'] / $dataRequest['sob'];
                return $this->json(true, $totalDevide, 'Calculate successfully', HttpStatusCodes::OK);
            } else {
                return $this->json(false, [], 'Values must be numeric', HttpStatusCodes::UNPROCESSABLE_ENTITY);
            }
        } else {
            return $this->json(false, [], 'Please provide soa and sob', HttpStatusCodes::BAD_REQUEST);
        }
    }

    public function actionAverage()
    {
        $data = Yii::$app->request->post();
        $numbers = [];
        // Collect all numbers from the request
        foreach ($data as $key => $value) {
            if (strpos($key, 'so') === 0) {
                $numbers[] = $value;
            }
        }
        foreach ($numbers as $number) {
            if (!is_numeric($number)) {
                return $this->json(false, [], 'All values must be numeric', HttpStatusCodes::UNPROCESSABLE_ENTITY);
            }
        }
        // Calculate average
        $total = array_sum($numbers);
        $count = count($numbers);
        $average = $total / $count;
        return $this->json(true,  $average, 'Calculate average successfully', HttpStatusCodes::OK);
    }
}
