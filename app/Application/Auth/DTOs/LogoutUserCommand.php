<?php

namespace App\Application\Auth\DTOs;

use App\Domain\Auth\ValueObjects\JWTToken;

final class LogoutUserCommand
{
    public readonly JWTToken $token;

    public readonly string $audience;

    public function __construct(JWTToken $token, string $audience)
    {
        $this->token = $token;
        $this->audience = $audience;
    }

    public function toArray(): array
    {
        return [
            'audience' => $this->audience,
            'agent_id' => $this->token->getAgentId(),
        ];
    }
}
