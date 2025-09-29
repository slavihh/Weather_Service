<?php

declare(strict_types=1);

namespace App\Service\Validation;

final class FreshnessValidatorService implements FreshnessValidatorServiceInterface
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';
    private const int TTL_SECONDS = 3600;
    private const string LAST_TIME_UPDATED = 'lastTimeUpdated';

    public function isFresh(array $cacheData): bool
    {
        $last = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $cacheData[self::LAST_TIME_UPDATED]);
        if (!$last) {
            return false;
        }

        return (new \DateTimeImmutable())->getTimestamp() - $last->getTimestamp() < self::TTL_SECONDS;
    }
}
