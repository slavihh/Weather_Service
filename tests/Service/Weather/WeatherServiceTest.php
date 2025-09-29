<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\ProviderResponse;
use App\Entity\WeatherHistory;
use App\Provider\ExternalProviderInterface;
use App\Repository\WeatherHistoryRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Validation\FreshnessValidatorServiceInterface;
use App\Service\Weather\Formatter\WeatherFormatterServiceInterface;
use App\Service\Weather\WeatherService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WeatherServiceTest extends TestCase
{
    /** @var MockObject&ExternalProviderInterface */
    private ExternalProviderInterface $provider;

    /** @var MockObject&CacheServiceInterface */
    private CacheServiceInterface $cache;

    /** @var MockObject&WeatherHistoryRepositoryInterface */
    private WeatherHistoryRepositoryInterface $repo;

    /** @var MockObject&WeatherFormatterServiceInterface */
    private WeatherFormatterServiceInterface $formatter;

    /** @var MockObject&FreshnessValidatorServiceInterface */
    private FreshnessValidatorServiceInterface $freshness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->createMock(ExternalProviderInterface::class);
        $this->cache = $this->createMock(CacheServiceInterface::class);
        $this->repo = $this->createMock(WeatherHistoryRepositoryInterface::class);
        $this->formatter = $this->createMock(WeatherFormatterServiceInterface::class);
        $this->freshness = $this->createMock(FreshnessValidatorServiceInterface::class);
    }

    public function testReturnsCachedFreshValue(): void
    {
        $this->cache->method('get')->willReturn([
            'temp' => 20.0,
            'lastTimeUpdated' => '2025-09-27 12:00:00',
        ]);
        $this->freshness->method('isFresh')->willReturn(true);
        $this->formatter->method('formatWithTrend')->willReturn('20 °C 🥵');

        $service = new WeatherService($this->provider, $this->cache, $this->repo, $this->formatter, $this->freshness);
        $result = $service->getTemperature('Sofia', 'BG');

        $this->assertSame('20 °C 🥵', $result);
    }

    public function testFallsBackToTodayRecordIfCacheStale(): void
    {
        $this->cache->method('get')->willReturn([
            'temp' => 15.0,
            'lastTimeUpdated' => '2025-09-27 08:00:00',
        ]);
        $this->freshness->method('isFresh')->willReturn(false);

        $todayRecord = $this->createMock(WeatherHistory::class);
        $todayRecord->method('getTemperature')->willReturn(17.0);
        $this->repo->method('findForToday')->willReturn($todayRecord);

        $this->formatter->method('formatWithTrend')->willReturn('17 °C 🥶');

        $service = new WeatherService($this->provider, $this->cache, $this->repo, $this->formatter, $this->freshness);
        $result = $service->getTemperature('Sofia', 'BG');

        $this->assertSame('17 °C 🥶', $result);
    }

    public function testCallsProviderWhenCacheAndRepoEmpty(): void
    {
        $this->cache->method('get')->willReturn(null);
        $this->repo->method('findForToday')->willReturn(null);
        $this->provider->method('request')->willReturn(ProviderResponse::success(18.5));
        $this->formatter->method('formatWithTrend')->willReturn('18.5 °C -');

        $service = new WeatherService($this->provider, $this->cache, $this->repo, $this->formatter, $this->freshness);
        $result = $service->getTemperature('Sofia', 'BG');

        $this->assertSame('18.5 °C -', $result);
    }

    public function testFallsBackToCacheWhenProviderFails(): void
    {
        $this->cache->method('get')->willReturn([
            'temp' => 12.0,
            'lastTimeUpdated' => '2025-09-27 09:00:00',
        ]);
        $this->freshness->method('isFresh')->willReturn(false);
        $this->repo->method('findForToday')->willReturn(null);

        $this->provider->method('request')->willReturn(ProviderResponse::failure('Transport error'));
        $this->formatter->method('formatWithTrend')->willReturn('12 °C -');

        $service = new WeatherService($this->provider, $this->cache, $this->repo, $this->formatter, $this->freshness);
        $result = $service->getTemperature('Sofia', 'BG');

        $this->assertSame('12 °C -', $result);
    }

    public function testThrowsWhenProviderReturnsNullTemperature(): void
    {
        $this->cache->method('get')->willReturn(null);
        $this->repo->method('findForToday')->willReturn(null);

        $badResponse = ProviderResponse::success(0.0);
        $ref = new \ReflectionClass($badResponse);
        $prop = $ref->getProperty('temperature');
        $prop->setAccessible(true);
        $prop->setValue($badResponse, null);

        $this->provider->method('request')->willReturn($badResponse);

        $service = new WeatherService(
            $this->provider,
            $this->cache,
            $this->repo,
            $this->formatter,
            $this->freshness
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Provider returned null temperature');

        $service->getTemperature('Sofia', 'BG');
    }
}
