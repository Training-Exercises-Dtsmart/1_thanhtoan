<?php

namespace app\components;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;
use yii\httpclient\Exception;

class WeatherComponent extends Component
{
    public $apiKey;
    public $apiUrl;

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