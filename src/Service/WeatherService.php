<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WeatherService
{
    private const string TEMPERATURE_KEY = 'temp';
    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly ExternalProviderInterface $externalProvider, private readonly CacheItemPoolInterface $cache)
    {
    }

    public function getTemperature(string $city, string $countryCode): string
    {
        $itemKey = \sprintf('%s_%s_%s', self::TEMPERATURE_KEY, $countryCode, $city);
        $cacheItem = $this->cache->getItem($itemKey);

        $cacheData = $cacheItem->isHit() ? $cacheItem->get() : null;

        // if cache is fresh (same as before)
        if ($cacheData && \is_array($cacheData) && $this->isFresh($cacheData)) {
            return $this->formatWithTrend($cacheData);
        }

        // fetch from provider
        $tempResponse = $this->externalProvider->request($city, $countryCode);

        if (!$tempResponse->isSuccess()) {
            if (Response::HTTP_NOT_FOUND === $tempResponse->getStatusCode()) {
                throw new NotFoundHttpException('City or Country not found!');
            }
            if ($cacheData && \is_array($cacheData)) {
                return $this->formatWithTrend($cacheData);
            }
        }

        $currentTemp = $tempResponse->getTemperature();
        $today = (new \DateTimeImmutable())->format('Y-m-d');

        // update history
        $history = $cacheData['history'] ?? [];
        $history[] = ['date' => $today, 'temp' => $currentTemp];
        $history = \array_slice($history, -10); // keep only last 10

        // save back
        $cacheItem->set([
            'temp' => $currentTemp,
            'lastTimeUpdated' => (new \DateTimeImmutable())->format(self::DATETIME_FORMAT),
            'history' => $history,
        ]);
        $cacheItem->expiresAfter(3600);
        $this->cache->save($cacheItem);

        return $this->formatWithTrend($cacheItem->get());
    }

    /**
     * @param array<mixed> $cacheData
     */
    private function formatWithTrend(array $cacheData): string
    {
        $currentTemp = (float) $cacheData['temp'];
        $history = $cacheData['history'] ?? [];

        if (\count($history) > 1) {
            $avg = \array_sum(\array_column($history, 'temp')) / \count($history);
            $diff = $currentTemp - $avg;
        } else {
            $diff = 0;
        }

        $suffix = match (true) {
            $diff > 0.5 => '🥵',
            $diff < -0.5 => '🥶',
            default => '',
        };

        if ('' === $suffix) {
            return (string) $currentTemp;
        }

        return \sprintf('%s %s', $currentTemp, $suffix);
    }

    /**
     * @param array<mixed> $cacheData
     */
    private function isFresh(array $cacheData): bool
    {
        $last = \DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $cacheData['lastTimeUpdated']);
        if (false === $last instanceof \DateTimeImmutable) {
            return false;
        }
        $now = new \DateTimeImmutable();
        $diff = $now->diff($last);

        return 0 === $diff->h && 0 === $diff->days;
    }
}
