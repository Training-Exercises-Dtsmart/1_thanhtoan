<?php

namespace app\modules\controllers;

use Yii;
use app\controllers\Controller;
use common\helpers\HttpStatusCodes;

//bcmul

class CalculatorController extends Controller
{
    public function actionIndex()
    {
        $dataRequest = Yii::$app->request->post();

        if (empty($dataRequest['soa']) || empty($dataRequest['sob'])) {
            return $this->json(false, [], 'Please provide soa and sob', 400);
        }
        if (!ctype_digit($dataRequest['soa']) || !ctype_digit($dataRequest['sob'])) {
            return $this->json(false, [], 'Values must be numeric', 422);
        }

        $soa = $dataRequest['soa'];
        $sob = $dataRequest['sob'];

        $total = $this->nhan($soa, $sob);

        return $this->json(true, $total, 'Calculate successfully', 200); // HttpStatusCodes::OK
    }

    // Thêm hàm multiplyLargeNumbers vào trong controller của bạn
    private function nhan($num1, $num2)
    {
        $len1 = strlen($num1);
        $len2 = strlen($num2);
        var_dump($len1);
        var_dump($num1);
        var_dump($num1[1] - '0');

        var_dump('dsadas');
        var_dump($len2);
        var_dump($num2);
        var_dump($num2[1] - '0');
//        var_dump($len2);

        // Kết quả ban đầu là mảng các số 0
        $result = array_fill(0, $len1 + $len2, 0);
//        var_dump($result);
//        die;

        // Lặp qua từng chữ số của num1 và num2
        for ($i = $len1 - 1; $i >= 0; $i--) {
            for ($j = $len2 - 1; $j >= 0; $j--) {
                $product = ($num1[$i] - '0') * ($num2[$j] - '0');
                $sum = $product + $result[$i + $j + 1];

                $result[$i + $j + 1] = $sum % 10;
                $result[$i + $j] += intdiv($sum, 10);
            }
        }

        // Chuyển mảng kết quả thành chuỗi, bỏ qua các số 0 ở đầu
        $resultStr = implode('', $result);
        return ltrim($resultStr, '0') ?: '0';
    }
}