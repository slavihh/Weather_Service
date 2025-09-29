<?php

declare(strict_types=1);

namespace App\Service\Validation;

interface FreshnessValidatorServiceInterface
{
    /**
     * @param array{temp: float, lastTimeUpdated: string} $cacheData
     */
    public function isFresh(array $cacheData): bool;
}
