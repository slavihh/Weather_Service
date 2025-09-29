<?php

declare(strict_types=1);

namespace App\Provider\OpenWeather;

use App\DTO\ProviderResponse;
use App\Provider\ExternalProviderInterface;
use App\Provider\QueryFactoryInterface;
use App\Provider\ResponseDenormalizerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenWeatherProvider implements ExternalProviderInterface
{
    private const string METHOD_GET = 'GET';
    private const string ERROR_TRANSPORT = 'Transport error: ';
    private const string QUERY = 'query';

    public function __construct(
        private readonly HttpClientInterface $openWeatherClient,
        private readonly ResponseDenormalizerInterface $denormalizer,
        private readonly QueryFactoryInterface $queryFactory,
    ) {
    }

    public function request(string $city, string $countryCode): ProviderResponse
    {
        try {
            $rawResponse = $this->openWeatherClient->request(self::METHOD_GET, '', [
                self::QUERY => $this->queryFactory->build($city, $countryCode),
            ]);

            return $this->denormalizer->denormalize($rawResponse);
        } catch (TransportExceptionInterface $e) {
            return ProviderResponse::failure(self::ERROR_TRANSPORT.$e->getMessage());
        }
    }
}
