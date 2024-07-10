<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class WeatherComponent extends Component
{
    public $apiKey;
    public $apiUrl = 'http://api.weatherapi.com/v1/current.json';

    public function getWeather($city)
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiUrl)
            ->setData(['q' => $city, 'key' => $this->apiKey])
            ->send();
        if ($response->isOk) {
            return $response->data;
        }

        return null;
    }
}