<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Psr\Cache\CacheItemPoolInterface;

final class CacheService implements CacheServiceInterface
{
    private const string CACHE_KEY_FORMAT = 'temp_%s_%s';
    private const string FIELD_TEMP = 'temp';
    private const string FIELD_UPDATED = 'lastTimeUpdated';
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly CacheItemPoolInterface $cache)
    {
    }

    public function get(string $city, string $countryCode): ?array
    {
        $itemKey = \sprintf(self::CACHE_KEY_FORMAT, $countryCode, $city);
        $cacheItem = $this->cache->getItem($itemKey);

        return $cacheItem->isHit() ? $cacheItem->get() : null;
    }

    public function save(string $city, string $countryCode, float $temp): void
    {
        $itemKey = \sprintf(self::CACHE_KEY_FORMAT, $countryCode, $city);
        $cacheItem = $this->cache->getItem($itemKey);
        $cacheItem->set([
            self::FIELD_TEMP => $temp,
            self::FIELD_UPDATED => (new \DateTimeImmutable())->format(self::DATE_FORMAT),
        ]);
        $this->cache->save($cacheItem);
    }
}
