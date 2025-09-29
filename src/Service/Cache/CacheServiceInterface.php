<?php

declare(strict_types=1);

namespace App\Service\Cache;

interface CacheServiceInterface
{
    /**
     * @return array{temp: float, lastTimeUpdated: string}|null
     */
    public function get(string $city, string $countryCode): ?array;

    public function save(string $city, string $countryCode, float $temp): void;
}
