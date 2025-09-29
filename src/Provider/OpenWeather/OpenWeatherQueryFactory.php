<?php

declare(strict_types=1);

namespace App\Provider\OpenWeather;

use App\Provider\QueryFactoryInterface;

final class OpenWeatherQueryFactory implements QueryFactoryInterface
{
    private const string UNITS = 'metric';
    private const string PARAM_QUERY = 'q';
    private const string PARAM_UNITS = 'units';
    private const string PARAM_APPID = 'appid';
    private const string CITY_FORMAT = '%s,%s';

    public function __construct(private readonly string $apiKey)
    {
    }

    public function build(string $city, string $countryCode): array
    {
        return [
            self::PARAM_QUERY => \sprintf(self::CITY_FORMAT, $city, $countryCode),
            self::PARAM_UNITS => self::UNITS,
            self::PARAM_APPID => $this->apiKey,
        ];
    }
}
