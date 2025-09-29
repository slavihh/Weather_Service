<?php

declare(strict_types=1);

namespace App\Tests\Provider\OpenWeather;

use App\Provider\OpenWeather\OpenWeatherQueryFactory;
use PHPUnit\Framework\TestCase;

final class OpenWeatherQueryFactoryTest extends TestCase
{
    public function testBuildsCorrectQueryArray(): void
    {
        $factory = new OpenWeatherQueryFactory('test-api-key');

        $result = $factory->build('Sofia', 'BG');

        $this->assertSame([
            'q' => 'Sofia,BG',
            'units' => 'metric',
            'appid' => 'test-api-key',
        ], $result);
    }

    public function testDifferentCityAndCountry(): void
    {
        $factory = new OpenWeatherQueryFactory('another-key');

        $result = $factory->build('London', 'UK');

        $this->assertSame([
            'q' => 'London,UK',
            'units' => 'metric',
            'appid' => 'another-key',
        ], $result);
    }
}
