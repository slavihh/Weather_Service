<?php

declare(strict_types=1);

namespace App\Service\Weather;

use App\Provider\ExternalProviderInterface;
use App\Repository\WeatherHistoryRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Validation\FreshnessValidatorServiceInterface;
use App\Service\Weather\Formatter\WeatherFormatterServiceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WeatherService implements WeatherServiceInterface
{
    private const string ERROR_CITY_NOT_FOUND = 'City or Country not found!';
    private const string ERROR_NULL_TEMP = 'Provider returned null temperature';
    private const string ERROR_PROVIDER_GENERIC = 'Provider returned error';
    private const string TEMP = 'temp';

    public function __construct(
        private readonly ExternalProviderInterface $externalProvider,
        private readonly CacheServiceInterface $cacheService,
        private readonly WeatherHistoryRepositoryInterface $historyRepository,
        private readonly WeatherFormatterServiceInterface $formatter,
        private readonly FreshnessValidatorServiceInterface $freshnessValidator,
    ) {
    }

    public function getTemperature(string $city, string $countryCode): string
    {
        $cacheData = $this->cacheService->get($city, $countryCode);

        if ($cacheData && $this->freshnessValidator->isFresh($cacheData)) {
            return $this->formatter->formatWithTrend($city, $countryCode, $cacheData[self::TEMP]);
        }

        $todayRecord = $this->historyRepository->findForToday($city, $countryCode);
        if (null !== $todayRecord) {
            $this->cacheService->save($city, $countryCode, $todayRecord->getTemperature());

            return $this->formatter->formatWithTrend($city, $countryCode, $todayRecord->getTemperature());
        }

        $tempResponse = $this->externalProvider->request($city, $countryCode);
        if (!$tempResponse->isSuccess()) {
            if (self::ERROR_PROVIDER_GENERIC === $tempResponse->getError()) {
                throw new NotFoundHttpException(self::ERROR_CITY_NOT_FOUND);
            }
            if ($cacheData) {
                return $this->formatter->formatWithTrend($city, $countryCode, $cacheData[self::TEMP]);
            }
        }

        $currentTemp = $tempResponse->getTemperature();
        if (null === $currentTemp) {
            throw new \RuntimeException(self::ERROR_NULL_TEMP);
        }

        $this->historyRepository->record($city, $countryCode, $currentTemp);
        $this->cacheService->save($city, $countryCode, $currentTemp);

        return $this->formatter->formatWithTrend($city, $countryCode, $currentTemp);
    }
}
