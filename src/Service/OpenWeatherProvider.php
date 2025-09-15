<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\ProviderResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenWeatherProvider implements ExternalProviderInterface
{
    private const string UNITS = 'metric';

    public function __construct(private readonly HttpClientInterface $openWeatherClient)
    {
    }

    public function request(string $city, string $countryCode): ProviderResponse
    {
        $tempResponse = $this->openWeatherClient->request('POST', 'https://api.openweathermap.org/data/2.5/weather', ['query' => [
            'q' => \sprintf('%s,%s', $city, $countryCode),
            'units' => self::UNITS,
            'appid' => 'a2c11f158bc9fe95d2ea9ef4a5c3cd40',
        ]]);

        return $this->denormalize($tempResponse);
    }

    private function denormalize(ResponseInterface $rawResponse): ProviderResponse
    {
        $response = new ProviderResponse();
        $arrayResponse = \json_decode($rawResponse->getContent(), true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            $response->setIsSuccess(false);

            return $response;
        }
        $response->setResponse($arrayResponse);
        if (200 !== $rawResponse->getStatusCode()) {
            $response->setIsSuccess(false);
            $response->setStatusCode($rawResponse->getStatusCode());

            return $response;
        }
        if (empty($arrayResponse) || false === \array_key_exists('main', $arrayResponse) || false === \array_key_exists('temp', $arrayResponse['main'])) {
            return $response;
        }

        $response->setTemperature((float) $arrayResponse['main']['temp']);
        $response->setIsSuccess(true);

        return $response;
    }
}
