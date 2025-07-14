<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

use App\Domain\Auth\ValueObjects\JWTToken;

final readonly class LogoutUserCommand
{
    public function __construct(public JWTToken $token, public string $audience) {}

    public function toArray(): array
    {
        return [
            'audience' => $this->audience,
            'agent_id' => $this->token->getAgentId(),
        ];
    }
}
