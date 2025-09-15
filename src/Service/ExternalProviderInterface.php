<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\ProviderResponse;

interface ExternalProviderInterface
{
    public function request(string $city, string $countryCode): ProviderResponse;
}
