<?php

declare(strict_types=1);

namespace App\Service\Weather\Formatter;

interface WeatherFormatterServiceInterface
{
    public function formatWithTrend(string $city, string $countryCode, float $currentTemp): string;
}
