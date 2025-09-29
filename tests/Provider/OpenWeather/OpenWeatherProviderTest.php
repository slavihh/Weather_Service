<?php

declare(strict_types=1);

namespace App\Tests\Provider\OpenWeather;

use App\DTO\ProviderResponse;
use App\Provider\OpenWeather\OpenWeatherProvider;
use App\Provider\QueryFactoryInterface;
use App\Provider\ResponseDenormalizerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenWeatherProviderTest extends TestCase
{
    /** @var MockObject&HttpClientInterface */
    private HttpClientInterface $client;

    /** @var MockObject&ResponseDenormalizerInterface */
    private ResponseDenormalizerInterface $denormalizer;

    /** @var MockObject&QueryFactoryInterface */
    private QueryFactoryInterface $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(HttpClientInterface::class);
        $this->denormalizer = $this->createMock(ResponseDenormalizerInterface::class);
        $this->factory = $this->createMock(QueryFactoryInterface::class);
    }

    public function testSuccessfulResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->factory->expects($this->once())
            ->method('build')
            ->with('Sofia', 'BG')
            ->willReturn(['q' => 'Sofia,BG']);

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', '', ['query' => ['q' => 'Sofia,BG']])
            ->willReturn($response);

        $expected = ProviderResponse::success(20.5);

        $this->denormalizer->expects($this->once())
            ->method('denormalize')
            ->with($response)
            ->willReturn($expected);

        $provider = new OpenWeatherProvider($this->client, $this->denormalizer, $this->factory);
        $result = $provider->request('Sofia', 'BG');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(20.5, $result->getTemperature());
    }

    public function testTransportExceptionReturnsFailure(): void
    {
        $this->factory->method('build')->willReturn(['q' => 'Sofia,BG']);

        $this->client->method('request')
            ->willThrowException(
                new class('network down') extends \Exception implements TransportExceptionInterface {}
            );

        $provider = new OpenWeatherProvider($this->client, $this->denormalizer, $this->factory);
        $result = $provider->request('Sofia', 'BG');

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Transport error: network down', $result->getError() ?? '');
    }

    public function testDenormalizerReturnsFailure(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->factory->method('build')->willReturn(['q' => 'Sofia,BG']);
        $this->client->method('request')->willReturn($response);

        $failure = ProviderResponse::failure('invalid response');
        $this->denormalizer->method('denormalize')->willReturn($failure);

        $provider = new OpenWeatherProvider($this->client, $this->denormalizer, $this->factory);
        $result = $provider->request('Sofia', 'BG');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('invalid response', $result->getError());
    }
}
