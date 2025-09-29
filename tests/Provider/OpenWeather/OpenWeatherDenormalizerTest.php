<?php

declare(strict_types=1);

namespace App\Tests\Provider\OpenWeather;

use App\Provider\OpenWeather\OpenWeatherDenormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenWeatherDenormalizerTest extends TestCase
{
    public function testSuccessfulDenormalization(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $payload = ['main' => ['temp' => 22.5]];

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(\json_encode($payload));

        $denormalizer = new OpenWeatherDenormalizer();
        $result = $denormalizer->denormalize($response);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(22.5, $result->getTemperature());
    }

    public function testInvalidJsonReturnsFailure(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn('{invalid-json');

        $denormalizer = new OpenWeatherDenormalizer();
        $result = $denormalizer->denormalize($response);

        $this->assertFalse($result->isSuccess());
    }

    public function testNon200StatusReturnsFailure(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $payload = ['error' => 'server error'];

        $response->method('getStatusCode')->willReturn(500);
        $response->method('getContent')->willReturn(\json_encode($payload));

        $denormalizer = new OpenWeatherDenormalizer();
        $result = $denormalizer->denormalize($response);

        $this->assertFalse($result->isSuccess());
    }

    public function testMissingTempReturnsFailure(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $payload = ['main' => []];

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(\json_encode($payload));

        $denormalizer = new OpenWeatherDenormalizer();
        $result = $denormalizer->denormalize($response);

        $this->assertFalse($result->isSuccess());
    }
}
