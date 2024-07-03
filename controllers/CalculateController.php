<?php


namespace app\controllers;

use Yii;
use yii\rest\Controller;

class CalculateController extends Controller
{

    public function actionTotal()
    {

        $dataRequest = Yii::$app->request->post();
        if (isset($dataRequest['soa']) && isset($dataRequest['sob'])) {
            if (is_numeric($dataRequest['soa']) && is_numeric($dataRequest['sob'])) {
                $total = $dataRequest['soa'] + $dataRequest['sob'];
                return [
                    'status' => true,
                    'data' => [
                        'Giatri' => $total,
                        'now' => date('d/m/Y')
                    ],
                    'message' => 'success'
                ];
            } else {
                return [
                    'status' => false,
                    'data' => null,
                    'message' => 'Giá trị phải là số'
                ];
            }
        } else {
            return [
                'status' => false,
                'data' => null,
                'message' => 'Phải nhập soa và sob'
            ];
        }
    }

    public function actionDivide()
    {
        $dataRequest = Yii::$app->request->post();
        if (isset($dataRequest['soa']) && isset($dataRequest['sob'])) {
            if (is_numeric($dataRequest['soa']) && is_numeric($dataRequest['sob'])) {
                $totalDevide = $dataRequest['soa'] / $dataRequest['sob'];
                return [
                    'status' => true,
                    'data' => [
                        'Giatri' => $totalDevide,
                        'now' => date('d/m/Y')
                    ],
                    'message' => 'success'
                ];
            } else {
                return [
                    'status' => false,
                    'data' => null,
                    'message' => 'Giá trị phải là số'
                ];
            }
        } else {
            return [
                'status' => false,
                'data' => null,
                'message' => 'Phải nhập soa và sob'
            ];
        }
    }

    public function actionAverage()
    {
        $data = Yii::$app->request->post();
        $numbers = [];
        //get all numbers
        foreach ($data as $key => $value) {
            if (strpos($key, 'so') === 0) {
                $numbers[] = $value;
            }
        }
        foreach ($numbers as $number) {
            if (!is_numeric($number)) {
                return [
                    'status' => false,
                    'data' => null,
                    'message' => 'Tất cả các giá trị phải là số'
                ];
            }
        }
        $total = array_sum($numbers);
        $count = count($numbers);
        $average = $total / $count;
        return [
            'status' => true,
            'data' => [
                'Giatri' => $average,
                'now' => date('d/m/Y')
            ],
            'message' => 'success'
        ];
    }
}
