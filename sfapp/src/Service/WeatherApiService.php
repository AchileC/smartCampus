<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service to interact with the weather API.
 */
class WeatherApiService
{
    private string $sharedSecret; // Secret key used for generating the API signature
    private string $baseUrl; // Base URL for the weather API
    private HttpClientInterface $httpClient; // Symfony HTTP client for making API requests
    private ?array $weatherData = null; // Holds the fetched weather data
    private CacheInterface $cache; // Cache for data storage (reduce API calls)

    /**
     * Constructor
     *
     * @param string $sharedSecret Secret key for API signature
     * @param string $baseUrl Base URL for the API
     * @param HttpClientInterface $httpClient HTTP client for API requests
     */
    public function __construct(string $sharedSecret, string $baseUrl, HttpClientInterface $httpClient, CacheInterface $cache)
    {
        $this->sharedSecret = $sharedSecret;
        $this->baseUrl = $baseUrl;
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /**
     * Generates a signed URL for the weather API.
     *
     * @param string $lat Latitude of the location
     * @param string $lon Longitude of the location
     * @param string $apiKey API key for authentication
     * @param int $expire Expiration time for the signed URL
     *
     * @return string The complete signed URL
     */
    private function getSignedUrl(string $lat, string $lon, string $apiKey, int $expire): string
    {
        $params = [
            'lat' => $lat,
            'lon' => $lon,
            'apikey' => $apiKey,
            'expire' => $expire,
        ];
        $query = '/packages/basic-day?' . http_build_query($params);
        $sig = hash_hmac('sha256', $query, $this->sharedSecret);
        return rtrim($this->baseUrl, '/') . $query . '&sig=' . $sig;
    }


    /**
     * Fetches weather data for a given location.
     *
     * @param string $lat Latitude of the location.
     * @param string $lon Longitude of the location.
     * @param string $apiKey API key for authentication.
     *
     * @throws \RuntimeException If the API request fails.
     */
    public function fetchWeatherData(string $lat, string $lon, string $apiKey): void
    {
        $expire = time() + 3600; // Set expiration time to 1 hour from now
        $url = $this->getSignedUrl($lat, $lon, $apiKey, $expire); // Generate the signed URL

        // Store the result in cache with a 1-hour expiration
        $this->weatherData = $this->cache->get('weather_data', function (ItemInterface $item) use ($url) {
            $item->expiresAfter(3600); // Cache for 1 hour
            try {
                $response = $this->httpClient->request('GET', $url);
                return $response->toArray();
            } catch (\Exception $e) {
                throw new \RuntimeException('Error fetching weather data: ' . $e->getMessage());
            }
        });
    }


    /**
     * Ensures that weather data has been loaded before attempting to access it.
     *
     * @throws \RuntimeException if the data has not been loaded
     */
    private function ensureWeatherDataIsLoaded(): void
    {
        if ($this->weatherData === null) {
            throw new \RuntimeException('Weather data not loaded. Call fetchWeatherData() first.');
        }
    }

    /**
     * Retrieves the weather forecast.
     *
     * @param int $days Number of days for the forecast (default is 4).
     *
     * @return array The forecasted weather data.
     *
     * @throws \RuntimeException If the weather data is not loaded.
     */
    public function getForecast(int $days = 4): array
    {
        $this->ensureWeatherDataIsLoaded(); // Ensure data is available

        // Extract data from the weatherData array
        $dates = $this->weatherData['data_day']['time'];
        $tempsMax = $this->weatherData['data_day']['temperature_max'];
        $tempsMin = $this->weatherData['data_day']['temperature_min'];
        $precipitations = $this->weatherData['data_day']['precipitation'];
        $pictocodes = $this->weatherData['data_day']['pictocode'];

        // Build the forecast array, limiting the number of days to the $days parameter
        $forecast = [];
        foreach (array_slice($dates, 0, $days) as $index => $date) {
            $forecast[] = [
                'date' => $date,
                'temperature_max' => $tempsMax[$index] ?? null,
                'temperature_min' => $tempsMin[$index] ?? null,
                'precipitation' => $precipitations[$index] ?? null,
                'pictocode' => $pictocodes[$index] ?? null,
            ];
        }

        return $forecast;
    }

    /**
     * Retrieves the maximum temperatures for the forecasted days.
     *
     * @return array Maximum temperatures for up to 4 days
     */
    public function getTemperatureMax(): array
    {
        $this->ensureWeatherDataIsLoaded();
        return array_slice($this->weatherData['data_day']['temperature_max'] ?? [], 0, 4);
    }

    /**
     * Retrieves the minimum temperatures for the forecasted days.
     *
     * @return array Minimum temperatures for up to 4 days
     */
    public function getTemperatureMin(): array
    {
        $this->ensureWeatherDataIsLoaded();
        return array_slice($this->weatherData['data_day']['temperature_min'] ?? [], 0, 4);
    }

    /**
     * Retrieves precipitation data for the forecasted days.
     *
     * @return array Precipitation values for up to 4 days
     */
    public function getPrecipitations(): array
    {
        $this->ensureWeatherDataIsLoaded();
        return array_slice($this->weatherData['data_day']['precipitation'] ?? [], 0, 4);
    }

    /**
     * Retrieves pictocode data for the forecasted days.
     *
     * @return array Pictocodes for up to 4 days
     */
    public function getPictocodes(): array
    {
        $this->ensureWeatherDataIsLoaded();
        return array_slice($this->weatherData['data_day']['pictocode'] ?? [], 0, 4);
    }
}