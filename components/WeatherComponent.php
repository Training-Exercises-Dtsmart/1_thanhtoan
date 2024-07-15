<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;
use yii\httpclient\Exception;

class WeatherComponent extends Component
{
    public $apiKey;
    public $apiUrl = 'http://api.weatherapi.com/v1/current.json';
    
    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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