<?php

namespace App\Application\Wallet\Responses;

final class WalletOperationResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly mixed $data = null,
        public readonly ?array $errors = null
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
