<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\ValueObjects\DeviceInfo;
use App\Domain\Auth\ValueObjects\JWTToken;
use DateTimeImmutable;

final readonly class LoginSuccessful
{
    public function __construct(
        private Agent $agent,
        private string $audience,
        private DeviceInfo $deviceInfo,
        private JWTToken $accessToken,
        private string $sessionId,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(
        Agent $agent,
        string $audience,
        DeviceInfo $deviceInfo,
        JWTToken $accessToken,
        string $sessionId
    ): self {
        return new self($agent, $audience, $deviceInfo, $accessToken, $sessionId, new DateTimeImmutable());
    }

    public function agent(): Agent
    {
        return $this->agent;
    }

    public function audience(): string
    {
        return $this->audience;
    }

    public function deviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    public function accessToken(): JWTToken
    {
        return $this->accessToken;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'event' => 'login_successful',
            'agent_id' => $this->agent->id(),
            'username' => $this->agent->username()->value(),
            'agent_type' => $this->agent->agentType()->value(),
            'audience' => $this->audience,
            'session_id' => $this->sessionId,
            'jwt_token_id' => $this->accessToken->id(),
            'token_expires_at' => $this->accessToken->expiresAt()->format('Y-m-d H:i:s'),
            'ip_address' => $this->deviceInfo->ipAddress(),
            'user_agent' => $this->deviceInfo->userAgent(),
            'device_type' => $this->deviceInfo->deviceType(),
            'browser' => $this->deviceInfo->browser(),
            'os' => $this->deviceInfo->os(),
            'country' => $this->deviceInfo->country(),
            'city' => $this->deviceInfo->city(),
            'location' => $this->deviceInfo->location(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
