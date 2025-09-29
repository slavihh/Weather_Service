<?php

declare(strict_types=1);

namespace App\Provider;

use App\DTO\ProviderResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface ResponseDenormalizerInterface
{
    public function denormalize(ResponseInterface $response): ProviderResponse;
}
