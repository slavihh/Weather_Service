<?php

declare(strict_types=1);

namespace App\Tests\Service\Validation;

use App\Service\Validation\FreshnessValidatorService;
use PHPUnit\Framework\TestCase;

final class FreshnessValidatorServiceTest extends TestCase
{
    public function testReturnsTrueForFreshCache(): void
    {
        $validator = new FreshnessValidatorService();

        $recentTime = (new \DateTimeImmutable('-30 minutes'))->format('Y-m-d H:i:s');
        $cacheData = ['temp' => 20.0, 'lastTimeUpdated' => $recentTime];

        $this->assertTrue($validator->isFresh($cacheData));
    }

    public function testReturnsFalseForStaleCache(): void
    {
        $validator = new FreshnessValidatorService();

        $oldTime = (new \DateTimeImmutable('-2 hours'))->format('Y-m-d H:i:s');
        $cacheData = ['temp' => 20.0, 'lastTimeUpdated' => $oldTime];

        $this->assertFalse($validator->isFresh($cacheData));
    }

    public function testReturnsFalseForInvalidDateFormat(): void
    {
        $validator = new FreshnessValidatorService();

        $cacheData = ['temp' => 20.0, 'lastTimeUpdated' => 'invalid-date'];

        $this->assertFalse($validator->isFresh($cacheData));
    }

    public function testReturnsFalseForBoundaryExactlyOneHour(): void
    {
        $validator = new FreshnessValidatorService();

        $boundaryTime = (new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s');
        $cacheData = ['temp' => 20.0, 'lastTimeUpdated' => $boundaryTime];

        $this->assertFalse($validator->isFresh($cacheData));
    }
}
