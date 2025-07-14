<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

final readonly class OperationResponse
{
    public function __construct(public bool $success, public string $message, public array $data = []) {}

    public static function success(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
