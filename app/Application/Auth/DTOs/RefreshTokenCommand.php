<?php

namespace App\Application\Auth\DTOs;

final class RefreshTokenCommand
{
    public readonly string $refreshToken;

    public readonly string $audience;

    public function __construct(string $refreshToken, string $audience)
    {
        $this->refreshToken = $refreshToken;
        $this->audience = $audience;
    }

    public function toArray(): array
    {
        return [
            'audience' => $this->audience,
        ];
    }
}
