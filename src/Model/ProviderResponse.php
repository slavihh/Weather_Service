<?php

declare(strict_types=1);

namespace App\Model;

final class ProviderResponse
{
    private ?float $temperature = null;
    private bool $isSuccess = false;
    private int $statusCode = 200;
    /**
     * @var array<string, string|float>
     */
    private array $response = [];

    public function __construct()
    {
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): void
    {
        $this->temperature = $temperature;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): void
    {
        $this->isSuccess = $isSuccess;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return array<string, string|float>
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @param array<string, string|float> $response
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }
}
