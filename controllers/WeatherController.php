<?php

namespace app\controllers;

use yii\web\Controller;
use yii\httpclient\Client;
use app\models\WeatherMoscow;

class WeatherController extends Controller
{
    public function actionIndex()
    {
        $weather = self::get_data_from_api();

        self::insert_weather_to_db($weather);

        return $this->render('index', [
                'weather' => $weather,
//                'weather_table' => $weather_table,
            ]);
    }

    public function actionDatabase()
    {
        $weather_table = WeatherMoscow::find()->all();

        return $this->render('database', [
                'weather_table' => $weather_table,
        ]);
    }

    private function get_data_from_api()
    {
        $client = new Client();

        return $client->createRequest()
            ->setMethod('get')
            ->setUrl('http://api.worldweatheronline.com/premium/v1/past-weather.ashx')
            ->setData([
                'key' => 'e3ce250a888b400e83c110545170204',
                'q' => 'Moscow',
                'date' => '2016-01-01',
                'enddate' => '2016-01-03',
                'tp' => '1',
                'format' => 'json'
            ])
            ->send()->data['data']['weather'];
    }

    private function insert_weather_to_db($weather)
    {
        $weather_table = new WeatherMoscow();

        foreach ($weather as $daily_weather)
        {
            $weather_table->date_time = $daily_weather['date'];
            $daily_average = round(array_reduce($daily_weather['hourly'], function ($sum, $hourly_temp){
                if ($hourly_temp['time'] >= 7 && $hourly_temp['time'] <= 23)
                    $sum = $hourly_temp['tempC'] + $sum;
                return $sum;
            }, 0) / 16);
            $nightly_average = round(array_reduce($daily_weather['hourly'], function ($sum, $hourly_temp){
                if ($hourly_temp['time'] < 7)
                    $sum = $hourly_temp['tempC'] + $sum;
                return $sum;
            }, 0) / 8);

            $weather_table->daily_average_temp = $daily_average;
            $weather_table->nightly_average_temp = $nightly_average;
            $weather_table->save();
        }
    }

}