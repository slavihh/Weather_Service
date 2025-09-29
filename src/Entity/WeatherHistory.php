<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WeatherHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WeatherHistoryRepository::class)]
#[ORM\Table(name: 'weather_history')]
#[ORM\Index(columns: ['city', 'country_code', 'recorded_at'], name: 'idx_weather_history_city')]
class WeatherHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

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
        $this->id = Uuid::v4();
        $this->city = $city;
        $this->countryCode = \strtoupper($countryCode);
        $this->temperature = $temperature;
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
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

    public static function getTrendSuffix(float $diff): string
    {
        return match (true) {
            $diff > 0.5 => '🥵',
            $diff < -0.5 => '🥶',
            default => '-',
        };
    }
}
