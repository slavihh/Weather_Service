<?php

declare(strict_types=1);

namespace App\Service;

use App\Provider\ExternalProviderInterface;
use App\Repository\WeatherHistoryRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WeatherService
{
    public function __construct(
        private readonly ExternalProviderInterface $externalProvider,
        private readonly CacheItemPoolInterface $cache,
        private readonly WeatherHistoryRepositoryInterface $historyRepository,
    ) {
    }

    public function getTemperature(string $city, string $countryCode): string
    {
        $itemKey = \sprintf('temp_%s_%s', $countryCode, $city);
        $cacheItem = $this->cache->getItem($itemKey);
        /** @var array{temp: float, lastTimeUpdated: string}|null $cacheData */
        $cacheData = $cacheItem->isHit() ? $cacheItem->get() : null;

        // Cache fresh
        if ($cacheData && $this->isFresh($cacheData)) {
            return $this->formatWithTrend($city, $countryCode, $cacheData['temp']);
        }

        // Check DB if we already have a record for today
        $todayRecord = $this->historyRepository->findForToday($city, $countryCode);
        if (null !== $todayRecord) {
            $cacheItem->set([
                'temp' => $todayRecord->getTemperature(),
                'lastTimeUpdated' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
            $this->cache->save($cacheItem);

            return $this->formatWithTrend($city, $countryCode, $todayRecord->getTemperature());
        }

        // Fetch from provider
        $tempResponse = $this->externalProvider->request($city, $countryCode);
        if (!$tempResponse->isSuccess()) {
            if (Response::HTTP_NOT_FOUND === $tempResponse->getStatusCode()) {
                throw new NotFoundHttpException('City or Country not found!');
            }
            if ($cacheData) {
                return $this->formatWithTrend($city, $countryCode, $cacheData['temp']);
            }
        }

        $currentTemp = $tempResponse->getTemperature();
        if (null === $currentTemp) {
            throw new \RuntimeException('Provider returned null temperature');
        }

        // Save to DB and cache
        $this->historyRepository->record($city, $countryCode, $currentTemp);

        $cacheItem->set([
            'temp' => $currentTemp,
            'lastTimeUpdated' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
        $this->cache->save($cacheItem);

        return $this->formatWithTrend($city, $countryCode, $currentTemp);
    }

    private function formatWithTrend(string $city, string $countryCode, float $currentTemp): string
    {
        /** @var array<int, array{temp: float}> $recentHistory */
        $recentHistory = $this->historyRepository->findLastForCity($city, $countryCode, 10);

        if (\count($recentHistory) > 1) {
            $avg = \array_sum(\array_column($recentHistory, 'temp')) / \count($recentHistory);
            $diff = $currentTemp - $avg;
        } else {
            $diff = 0;
        }

        $suffix = match (true) {
            $diff > 0.5 => '🥵',
            $diff < -0.5 => '🥶',
            default => '-',
        };

        return \sprintf('%s %s', $currentTemp, $suffix);
    }

    /**
     * @param array{temp: float, lastTimeUpdated: string} $cacheData
     */
    private function isFresh(array $cacheData): bool
    {
        $last = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $cacheData['lastTimeUpdated']);
        if (!$last) {
            return false;
        }

        return (new \DateTimeImmutable())->getTimestamp() - $last->getTimestamp() < 3600;
    }
}
