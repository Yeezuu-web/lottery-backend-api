<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

final readonly class RefreshTokenCommand
{
    public function __construct(public string $refreshToken, public string $audience) {}

    public function toArray(): array
    {
        return [
            'audience' => $this->audience,
        ];
    }
}
