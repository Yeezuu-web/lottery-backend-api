<?php

namespace App\Application\Auth\DTOs;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\ValueObjects\TokenPair;

final class AuthenticateUserResponse
{
    public readonly Agent $agent;

    public readonly TokenPair $tokenPair;

    public readonly bool $success;

    public readonly ?string $message;

    public function __construct(
        Agent $agent,
        TokenPair $tokenPair,
        bool $success = true,
        ?string $message = null
    ) {
        $this->agent = $agent;
        $this->tokenPair = $tokenPair;
        $this->success = $success;
        $this->message = $message;
    }

    public static function success(Agent $agent, TokenPair $tokenPair, string $message = 'Authentication successful'): self
    {
        return new self($agent, $tokenPair, true, $message);
    }

    public static function failure(string $message): self
    {
        // For failure case, we don't have agent/tokens, but we need the interface to be consistent
        // In practice, use cases will throw exceptions for failures instead of returning failure responses
        throw new \InvalidArgumentException('Use exceptions for failure cases in use cases');
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'agent' => [
                'id' => $this->agent->id(),
                'username' => $this->agent->username()->value(),
                'email' => $this->agent->email(),
                'name' => $this->agent->name(),
                'agent_type' => $this->agent->agentType()->value(),
                'is_active' => $this->agent->isActive(),
            ],
            'tokens' => [
                'access_token' => $this->tokenPair->accessToken()->token(),
                'refresh_token' => $this->tokenPair->refreshToken()->token(),
                'access_expires_at' => $this->tokenPair->accessToken()->expiresAt()->format('c'),
                'refresh_expires_at' => $this->tokenPair->refreshToken()->expiresAt()->format('c'),
            ],
        ];
    }
}
