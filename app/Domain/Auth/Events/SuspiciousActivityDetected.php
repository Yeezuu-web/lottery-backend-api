<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Auth\ValueObjects\DeviceInfo;
use DateTimeImmutable;

final readonly class SuspiciousActivityDetected
{
    public function __construct(
        private string $username,
        private string $audience,
        private array $riskFactors,
        private string $threatLevel,
        private DeviceInfo $deviceInfo,
        private array $metadata,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(
        string $username,
        string $audience,
        array $riskFactors,
        string $threatLevel,
        DeviceInfo $deviceInfo,
        array $metadata = []
    ): self {
        return new self($username, $audience, $riskFactors, $threatLevel, $deviceInfo, $metadata, new DateTimeImmutable());
    }

    public function username(): string
    {
        return $this->username;
    }

    public function audience(): string
    {
        return $this->audience;
    }

    public function riskFactors(): array
    {
        return $this->riskFactors;
    }

    public function threatLevel(): string
    {
        return $this->threatLevel;
    }

    public function deviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'event' => 'suspicious_activity_detected',
            'username' => $this->username,
            'audience' => $this->audience,
            'risk_factors' => $this->riskFactors,
            'threat_level' => $this->threatLevel,
            'ip_address' => $this->deviceInfo->ipAddress(),
            'user_agent' => $this->deviceInfo->userAgent(),
            'device_type' => $this->deviceInfo->deviceType(),
            'browser' => $this->deviceInfo->browser(),
            'os' => $this->deviceInfo->os(),
            'country' => $this->deviceInfo->country(),
            'city' => $this->deviceInfo->city(),
            'location' => $this->deviceInfo->location(),
            'metadata' => $this->metadata,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
