<?php

namespace App\Service;

class WeatherApiService
{
    private string $sharedSecret;
    private string $baseUrl;

    public function __construct(string $sharedSecret, string $baseUrl)
    {
        $this->sharedSecret = $sharedSecret;
        $this->baseUrl = $baseUrl;
    }

    public function getSignedUrl(string $lat, string $lon, string $apiKey, int $expire): string
    {
        $query = "/packages/basic-1h?lat=$lat&lon=$lon&apikey=$apiKey&expire=$expire";

        $sig = hash_hmac("sha256", $query, $this->sharedSecret);

        return $this->baseUrl . $query . "&sig=" . $sig;
    }
}
