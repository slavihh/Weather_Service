<?php

declare(strict_types=1);

namespace App\Service\Weather;

interface WeatherServiceInterface
{
    public function getTemperature(string $city, string $countryCode): string;
}
