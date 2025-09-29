<?php

declare(strict_types=1);

namespace App\Provider;

use App\DTO\ProviderResponse;

interface ExternalProviderInterface
{
    public function request(string $city, string $countryCode): ProviderResponse;
}
