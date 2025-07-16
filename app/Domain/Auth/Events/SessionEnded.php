<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\ValueObjects\DeviceInfo;
use DateTimeImmutable;

final readonly class SessionEnded
{
    public function __construct(
        private Agent $agent,
        private string $audience,
        private string $sessionId,
        private string $logoutReason,
        private DeviceInfo $deviceInfo,
        private int $sessionDuration,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(
        Agent $agent,
        string $audience,
        string $sessionId,
        string $logoutReason,
        DeviceInfo $deviceInfo,
        int $sessionDuration
    ): self {
        return new self($agent, $audience, $sessionId, $logoutReason, $deviceInfo, $sessionDuration, new DateTimeImmutable());
    }

    public function agent(): Agent
    {
        return $this->agent;
    }

    public function audience(): string
    {
        return $this->audience;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function logoutReason(): string
    {
        return $this->logoutReason;
    }

    public function deviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    public function sessionDuration(): int
    {
        return $this->sessionDuration;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'event' => 'session_ended',
            'agent_id' => $this->agent->id(),
            'username' => $this->agent->username()->value(),
            'agent_type' => $this->agent->agentType()->value(),
            'audience' => $this->audience,
            'session_id' => $this->sessionId,
            'logout_reason' => $this->logoutReason,
            'session_duration' => $this->sessionDuration,
            'ip_address' => $this->deviceInfo->ipAddress(),
            'user_agent' => $this->deviceInfo->userAgent(),
            'device_type' => $this->deviceInfo->deviceType(),
            'browser' => $this->deviceInfo->browser(),
            'os' => $this->deviceInfo->os(),
            'country' => $this->deviceInfo->country(),
            'city' => $this->deviceInfo->city(),
            'location' => $this->deviceInfo->getLocationString(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
