<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Responses;

use JsonSerializable;

final readonly class AgentSettingsOperationResponse implements JsonSerializable
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?AgentSettingsResponse $data = null,
        public ?array $errors = null
    ) {}

    public static function success(string $message, ?AgentSettingsResponse $data = null): self
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

    public function jsonSerialize(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->data instanceof AgentSettingsResponse) {
            $response['data'] = $this->data->toArray();
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
