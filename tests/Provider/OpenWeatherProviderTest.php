<?php

declare(strict_types=1);

namespace App\Tests\Provider;

use App\Provider\OpenWeatherProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenWeatherProviderTest extends TestCase
{
    /** @var HttpClientInterface&MockObject */
    private HttpClientInterface $httpClient;
    private OpenWeatherProvider $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->provider = new OpenWeatherProvider($this->httpClient, 'test_api_key');
    }

    public function testSuccessfulResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(\json_encode([
            'main' => ['temp' => 21.5],
        ]));

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $result = $this->provider->request('Sofia', 'BG');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(21.5, $result->getTemperature());
    }

    public function testInvalidJson(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn('invalid JSON');

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->provider->request('Sofia', 'BG');

        $this->assertFalse($result->isSuccess());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertNull($result->getTemperature());
    }

    public function testMissingTempField(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(\json_encode([
            'main' => [],
        ]));

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->provider->request('Sofia', 'BG');

        $this->assertFalse($result->isSuccess());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertNull($result->getTemperature());
    }

    public function testNon200StatusCode(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getContent')->willReturn(\json_encode([]));

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->provider->request('Sofia', 'BG');

        $this->assertFalse($result->isSuccess());
        $this->assertSame(500, $result->getStatusCode());
    }

    public function testClientExceptionReturns404(): void
    {
        $dummyResponse = $this->createMock(ResponseInterface::class);

        $exception = new class($dummyResponse) extends \Exception implements ClientExceptionInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                parent::__construct('Not Found', 404);
                $this->response = $response;
            }

            public function getResponse(): ResponseInterface
            {
                return $this->response;
            }
        };

        $this->httpClient
            ->method('request')
            ->willThrowException($exception);

        $result = $this->provider->request('just a random test of city that is missing', 'XX');

        $this->assertFalse($result->isSuccess());
        $this->assertSame(404, $result->getStatusCode());
    }
}
