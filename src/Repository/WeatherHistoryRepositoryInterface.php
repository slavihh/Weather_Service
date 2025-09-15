<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WeatherHistory;

interface WeatherHistoryRepositoryInterface
{
    public function findForToday(string $city, string $countryCode): ?WeatherHistory;

    /**
     * @return array<int, array{temp: float}>
     */
    public function findLastForCity(string $city, string $countryCode, int $limit): array;

    public function record(string $city, string $countryCode, float $temp): void;
}
