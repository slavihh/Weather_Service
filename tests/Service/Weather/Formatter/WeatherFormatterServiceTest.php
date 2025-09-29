<?php

declare(strict_types=1);

namespace App\Tests\Service\Weather\Formatter;

use App\Repository\WeatherHistoryRepositoryInterface;
use App\Service\Weather\Formatter\WeatherFormatterService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WeatherFormatterServiceTest extends TestCase
{
    /** @var MockObject&WeatherHistoryRepositoryInterface */
    private WeatherHistoryRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->createMock(WeatherHistoryRepositoryInterface::class);
    }

    #[Test]
    public function formatsWithNeutralTrendWhenNoHistory(): void
    {
        $this->repo->method('findLastForCity')->willReturn([]);

        $formatter = new WeatherFormatterService($this->repo);
        $result = $formatter->formatWithTrend('Sofia', 'BG', 20.0);

        $this->assertSame('20 -', $result);
    }

    #[Test]
    public function formatsWithNeutralTrendWhenOnlyOneHistoryRecord(): void
    {
        $this->repo->method('findLastForCity')->willReturn([['temp' => 19.0]]);

        $formatter = new WeatherFormatterService($this->repo);
        $result = $formatter->formatWithTrend('Sofia', 'BG', 20.0);

        $this->assertSame('20 -', $result);
    }

    #[Test]
    public function formatsWithHotTrendWhenTempAboveAverage(): void
    {
        $this->repo->method('findLastForCity')->willReturn([
            ['temp' => 18.0],
            ['temp' => 19.0],
            ['temp' => 20.0],
        ]);

        $formatter = new WeatherFormatterService($this->repo);
        $result = $formatter->formatWithTrend('Sofia', 'BG', 25.0);

        $this->assertSame('25 🥵', $result);
    }

    #[Test]
    public function formatsWithColdTrendWhenTempBelowAverage(): void
    {
        $this->repo->method('findLastForCity')->willReturn([
            ['temp' => 25.0],
            ['temp' => 24.0],
            ['temp' => 23.0],
        ]);

        $formatter = new WeatherFormatterService($this->repo);
        $result = $formatter->formatWithTrend('Sofia', 'BG', 20.0);

        $this->assertSame('20 🥶', $result);
    }

    #[Test]
    public function formatsWithNeutralTrendWhenCloseToAverage(): void
    {
        $this->repo->method('findLastForCity')->willReturn([
            ['temp' => 20.0],
            ['temp' => 21.0],
        ]);

        $formatter = new WeatherFormatterService($this->repo);
        $result = $formatter->formatWithTrend('Sofia', 'BG', 20.4);

        $this->assertSame('20.4 -', $result);
    }
}
