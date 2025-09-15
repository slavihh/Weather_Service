<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WeatherHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeatherHistoryRepository::class)]
#[ORM\Table(name: 'weather_history')]
#[ORM\Index(columns: ['city', 'country_code', 'recorded_at'], name: 'idx_weather_history_city')]
class WeatherHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $city;

    #[ORM\Column(length: 2)]
    private string $countryCode;

    #[ORM\Column(type: 'float')]
    private float $temperature;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    public function __construct(string $city, string $countryCode, float $temperature)
    {
        $this->city = $city;
        $this->countryCode = \strtoupper($countryCode);
        $this->temperature = $temperature;
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
