<?php

declare(strict_types=1);

namespace App\Service\Weather\Formatter;

use App\Entity\WeatherHistory;
use App\Repository\WeatherHistoryRepositoryInterface;

final class WeatherFormatterService implements WeatherFormatterServiceInterface
{
    private const int HISTORY_LIMIT = 10;
    private const string FIELD_TEMP = 'temp';
    private const string FORMAT_OUTPUT = '%s %s';

    public function __construct(private readonly WeatherHistoryRepositoryInterface $historyRepository)
    {
    }

    public function formatWithTrend(string $city, string $countryCode, float $currentTemp): string
    {
        /** @var array<int, array{temp: float}> $recentHistory */
        $recentHistory = $this->historyRepository->findLastForCity($city, $countryCode, self::HISTORY_LIMIT);

        if (\count($recentHistory) > 1) {
            $avg = \array_sum(\array_column($recentHistory, self::FIELD_TEMP)) / \count($recentHistory);
            $diff = $currentTemp - $avg;
        } else {
            $diff = 0;
        }

        $suffix = WeatherHistory::getTrendSuffix($diff);

        return \sprintf(self::FORMAT_OUTPUT, $currentTemp, $suffix);
    }
}
