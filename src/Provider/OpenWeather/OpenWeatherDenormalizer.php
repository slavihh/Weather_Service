<?php

declare(strict_types=1);

namespace App\Provider\OpenWeather;

use App\DTO\ProviderResponse;
use App\Provider\ResponseDenormalizerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenWeatherDenormalizer implements ResponseDenormalizerInterface
{
    private const int HTTP_OK = 200;
    private const string FIELD_MAIN = 'main';
    private const string FIELD_TEMP = 'temp';
    private const string ERROR_INVALID_JSON = 'Invalid JSON response';
    private const string ERROR_PROVIDER = 'Provider returned error';
    private const string ERROR_MISSING_TEMP = 'Temperature missing in response';

    public function denormalize(ResponseInterface $response): ProviderResponse
    {
        $statusCode = $response->getStatusCode();
        $arrayResponse = \json_decode($response->getContent(false), true);

        if (\JSON_ERROR_NONE !== \json_last_error()) {
            return ProviderResponse::failure(self::ERROR_INVALID_JSON);
        }

        if (self::HTTP_OK !== $statusCode) {
            return ProviderResponse::failure(self::ERROR_PROVIDER, $arrayResponse ?? []);
        }

        if (empty($arrayResponse[self::FIELD_MAIN][self::FIELD_TEMP] ?? null)) {
            return ProviderResponse::failure(self::ERROR_MISSING_TEMP, $arrayResponse ?? []);
        }

        return ProviderResponse::success((float) $arrayResponse[self::FIELD_MAIN][self::FIELD_TEMP], $arrayResponse ?? []);
    }
}
