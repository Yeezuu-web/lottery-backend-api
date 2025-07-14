<?php

declare(strict_types=1);

namespace App\Application\Wallet\Responses;

final readonly class WalletOperationResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public mixed $data = null,
        public ?array $errors = null
    ) {}

    public static function success(string $message, mixed $data = null): self
    {
        return new self(
            success: true,
            message: $message,
            data: $data
        );
    }

    public static function failure(string $message, ?array $errors = null): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }
}
