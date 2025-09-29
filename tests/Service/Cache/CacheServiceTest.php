<?php

declare(strict_types=1);

namespace App\Tests\Service\Cache;

use App\Service\Cache\CacheService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class CacheServiceTest extends TestCase
{
    public function testGetReturnsNullWhenCacheMiss(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $item = $this->createMock(CacheItemInterface::class);

        $pool->method('getItem')->with('temp_BG_Sofia')->willReturn($item);
        $item->method('isHit')->willReturn(false);

        $service = new CacheService($pool);

        $this->assertNull($service->get('Sofia', 'BG'));
    }

    public function testGetReturnsDataWhenCacheHit(): void
    {
        $expected = ['temp' => 20.0, 'lastTimeUpdated' => '2025-09-27 12:00:00'];

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $item = $this->createMock(CacheItemInterface::class);

        $pool->method('getItem')->with('temp_BG_Sofia')->willReturn($item);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn($expected);

        $service = new CacheService($pool);
        $result = $service->get('Sofia', 'BG');

        $this->assertSame($expected, $result);
    }

    public function testSaveStoresTemperatureWithTimestamp(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $item = $this->createMock(CacheItemInterface::class);

        $pool->expects($this->once())
            ->method('getItem')
            ->with('temp_BG_Sofia')
            ->willReturn($item);

        $item->expects($this->once())
            ->method('set')
            ->willReturnCallback(function (array $data) {
                $this->assertSame(25.0, $data['temp']);
                $this->assertArrayHasKey('lastTimeUpdated', $data);

                return $this->createMock(CacheItemInterface::class);
            });

        $pool->expects($this->once())->method('save')->with($item);

        $service = new CacheService($pool);
        $service->save('Sofia', 'BG', 25.0);
    }
}
