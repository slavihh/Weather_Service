<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class WeatherRequest
{
    #[Assert\NotBlank(message: 'City is required.')]
    #[Assert\Type('string')]
    #[Assert\Length(min: 2, max: 100)]
    private string $city;

    #[Assert\NotBlank(message: 'Country code is required.')]
    #[Assert\Type('string')]
    #[Assert\Length(min: 2, max: 2, exactMessage: 'Country code must be exactly 2 letters.')]
    #[Assert\Regex(pattern: '/^[A-Z]{2}$/', message: 'Country code must be uppercase ISO 3166-1 alpha-2.')]
    private string $country;

    public function __construct(string $city, string $country)
    {
        $this->city = $city;
        $this->country = $country;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }
}
