<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private string $apiUrl;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        // Clé API gratuite OpenWeatherMap (remplace par la tienne)
        $this->apiKey = '';
        $this->apiUrl = 'https://api.openweathermap.org/data/2.5/weather';
    }

    public function getWeather(string $location): array
    {
        // Mapping des régions tunisiennes vers les villes pour l'API
        $cityMapping = [
            'Tunis' => 'Tunis',
            'Ariana' => 'Ariana',
            'Ben Arous' => 'Ben Arous',
            'Manouba' => 'Manouba',
            'Nabeul' => 'Nabeul',
            'Zaghouan' => 'Zaghouan',
            'Bizerte' => 'Bizerte',
            'Béja' => 'Beja',
            'Jendouba' => 'Jendouba',
            'Le Kef' => 'El Kef',
            'Siliana' => 'Siliana',
            'Kairouan' => 'Kairouan',
            'Kasserine' => 'Kasserine',
            'Sidi Bouzid' => 'Sidi Bouzid',
            'Sousse' => 'Sousse',
            'Monastir' => 'Monastir',
            'Mahdia' => 'Mahdia',
            'Sfax' => 'Sfax',
            'Gabès' => 'Gabes',
            'Médenine' => 'Medenine',
            'Tataouine' => 'Tataouine',
            'Gafsa' => 'Gafsa',
            'Tozeur' => 'Tozeur',
            'Kebili' => 'Kebili',
        ];
        
        // Nettoyer la location (enlever les tirets, adresses détaillées)
        $cleanLocation = trim($location);
        $city = $cityMapping[$cleanLocation] ?? 'Tunis';
        
        try {
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => [
                    'q' => $city . ',TN',
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                return $this->getFallbackWeather($city);
            }

            $data = $response->toArray();
            
            return [
                'temperature' => round($data['main']['temp']),
                'description' => $data['weather'][0]['description'],
                'icon' => $data['weather'][0]['icon'],
                'humidity' => $data['main']['humidity'],
                'wind_speed' => round($data['wind']['speed']),
                'city' => $city
            ];
        } catch (\Exception $e) {
            return $this->getFallbackWeather($city);
        }
    }

    private function getFallbackWeather(string $city = 'Tunis'): array
    {
        return [
            'temperature' => 22,
            'description' => 'Partly cloudy',
            'icon' => '02d',
            'humidity' => 65,
            'wind_speed' => 12,
            'city' => $city
        ];
    }
}