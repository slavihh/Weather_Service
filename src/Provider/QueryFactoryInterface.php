<?php

declare(strict_types=1);

namespace App\Provider;

interface QueryFactoryInterface
{
    /**
     * @return array<string, string|int|float>
     */
    public function build(string $city, string $countryCode): array;
}
