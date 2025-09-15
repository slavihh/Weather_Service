<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Model\ProviderResponse;
use App\Provider\ExternalProviderInterface;
use App\Repository\WeatherHistoryRepositoryInterface;
use App\Service\WeatherService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WeatherServiceTest extends TestCase
{
    /** @var ExternalProviderInterface&MockObject */
    private ExternalProviderInterface $externalProvider;

    /** @var CacheItemPoolInterface&MockObject */
    private CacheItemPoolInterface $cache;

    /** @var CacheItemInterface&MockObject */
    private CacheItemInterface $cacheItem;

    /** @var WeatherHistoryRepositoryInterface&MockObject */
    private WeatherHistoryRepositoryInterface $historyRepository;

    private WeatherService $weatherService;

    protected function setUp(): void
    {
        $this->externalProvider   = $this->createMock(ExternalProviderInterface::class);
        $this->cache              = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem          = $this->createMock(CacheItemInterface::class);
        $this->historyRepository  = $this->createMock(WeatherHistoryRepositoryInterface::class);

        $this->weatherService = new WeatherService(
            $this->externalProvider,
            $this->cache,
            $this->historyRepository
        );
    }

    public function testReturnsCachedTemperatureIfFresh(): void
    {
        $cacheData = [
            'temp' => 20.0,
            'lastTimeUpdated' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->cache->method('getItem')->willReturn($this->cacheItem);
        $this->cacheItem->method('isHit')->willReturn(true);
        $this->cacheItem->method('get')->willReturn($cacheData);

        $this->historyRepository->method('findLastForCity')->willReturn([]);

        $result = $this->weatherService->getTemperature('Berlin', 'DE');

        $this->assertStringStartsWith('20', $result);
    }

    public function testThrowsNotFoundWhenProviderReturns404(): void
    {
        $this->cache->method('getItem')->willReturn($this->cacheItem);
        $this->cacheItem->method('isHit')->willReturn(false);

        $response = $this->makeProviderResponse(false, Response::HTTP_NOT_FOUND);

        $this->externalProvider->method('request')->willReturn($response);

        $this->expectException(NotFoundHttpException::class);

        $this->weatherService->getTemperature('just strange city', 'XX');
    }

    public function testFallsBackToCacheWhenProviderFails(): void
    {
        $cacheData = [
            'temp' => 27.0,
            'lastTimeUpdated' => (new \DateTimeImmutable('-30 minutes'))->format('Y-m-d H:i:s'),
        ];

        $this->cache->method('getItem')->willReturn($this->cacheItem);
        $this->cacheItem->method('isHit')->willReturn(true);
        $this->cacheItem->method('get')->willReturn($cacheData);

        $response = $this->makeProviderResponse(false, 500);

        $this->externalProvider->method('request')->willReturn($response);
        $this->historyRepository->method('findLastForCity')->willReturn([]);

        $result = $this->weatherService->getTemperature('Sofia', 'BG');

        $this->assertStringStartsWith('27', $result);
    }

    public function testFetchesAndCachesTemperatureWhenNotFresh(): void
    {
        $oldCacheData = [
            'temp' => 23.0,
            'lastTimeUpdated' => (new \DateTimeImmutable('-2 hours'))->format('Y-m-d H:i:s'),
        ];

        $this->cache->method('getItem')->willReturn($this->cacheItem);
        $this->cacheItem->method('isHit')->willReturn(true);
        $this->cacheItem->method('get')->willReturnOnConsecutiveCalls(
            $oldCacheData,
            [
                'temp' => 25.0,
                'lastTimeUpdated' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );

        $response = $this->makeProviderResponse(true, 200, 25.0);

        $this->externalProvider->method('request')->willReturn($response);

        // record() is expected once
        $this->historyRepository
            ->expects($this->once())
            ->method('record')
            ->with('Sofia', 'BG', 25.0);

        $this->historyRepository->method('findLastForCity')->willReturn([]);

        $this->cacheItem->expects($this->once())->method('set');
        $this->cache->expects($this->once())->method('save')->with($this->cacheItem);

        $result = $this->weatherService->getTemperature('Sofia', 'BG');

        $this->assertStringStartsWith('25', $result);
    }

    private function makeProviderResponse(bool $success, int $statusCode, ?float $temp = null): ProviderResponse
    {
        $response = new ProviderResponse();
        $response->setIsSuccess($success);
        $response->setStatusCode($statusCode);

        if ($temp !== null) {
            $response->setTemperature($temp);
        }

        return $response;
    }
}
