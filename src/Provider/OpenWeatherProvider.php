<?php

declare(strict_types=1);

namespace App\Provider;

use App\Model\ProviderResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenWeatherProvider implements ExternalProviderInterface
{
    private const string UNITS = 'metric';

    public function __construct(
        private readonly HttpClientInterface $openWeatherClient,
        private readonly string $apiKey,
    ) {
    }

    public function request(string $city, string $countryCode): ProviderResponse
    {
        try {
            $rawResponse = $this->openWeatherClient->request('GET', '', [
                'query' => [
                    'q' => \sprintf('%s,%s', $city, $countryCode),
                    'units' => self::UNITS,
                    'appid' => $this->apiKey,
                ],
            ]);

            return $this->denormalize($rawResponse);
        } catch (ClientExceptionInterface $e) {
            return $this->errorResponse($e->getCode() ?: Response::HTTP_NOT_FOUND);
        } catch (RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return $this->errorResponse($e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function denormalize(ResponseInterface $rawResponse): ProviderResponse
    {
        $statusCode = $rawResponse->getStatusCode();
        $response = new ProviderResponse();
        $response->setStatusCode($statusCode);

        $arrayResponse = \json_decode($rawResponse->getContent(false), true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            return $this->errorResponse($statusCode);
        }

        $response->setResponse($arrayResponse);

        if (200 !== $statusCode) {
            return $this->errorResponse($statusCode);
        }

        if (empty($arrayResponse['main']['temp'] ?? null)) {
            return $this->errorResponse($statusCode);
        }

        $response->setTemperature((float) $arrayResponse['main']['temp']);
        $response->setIsSuccess(true);

        return $response;
    }

    private function errorResponse(int $statusCode): ProviderResponse
    {
        $response = new ProviderResponse();
        $response->setIsSuccess(false);
        $response->setStatusCode($statusCode);

        return $response;
    }
}
