<?php

declare(strict_types=1);

namespace App\DTO;

final class ProviderResponse
{
    private ?float $temperature = null;
    private bool $isSuccess = false;
    private ?string $error = null;

    /**
     * @var array<string, mixed>
     */
    private array $rawResponse = [];

    /**
     * @param array<string, mixed> $rawResponse
     */
    public static function success(float $temperature, array $rawResponse = []): self
    {
        $self = new self();
        $self->temperature = $temperature;
        $self->isSuccess = true;
        $self->rawResponse = $rawResponse;

        return $self;
    }

    /**
     * @param array<string, mixed> $rawResponse
     */
    public static function failure(string $error, array $rawResponse = []): self
    {
        $self = new self();
        $self->isSuccess = false;
        $self->error = $error;
        $self->rawResponse = $rawResponse;

        return $self;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }
}
