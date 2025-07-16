<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Auth\ValueObjects\DeviceInfo;
use DateTimeImmutable;

final readonly class LoginBlocked
{
    public function __construct(
        private string $username,
        private string $audience,
        private string $blockReason,
        private DeviceInfo $deviceInfo,
        private int $attemptCount,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(
        string $username,
        string $audience,
        string $blockReason,
        DeviceInfo $deviceInfo,
        int $attemptCount
    ): self {
        return new self($username, $audience, $blockReason, $deviceInfo, $attemptCount, new DateTimeImmutable());
    }

    public function username(): string
    {
        return $this->username;
    }

    public function audience(): string
    {
        return $this->audience;
    }

    public function blockReason(): string
    {
        return $this->blockReason;
    }

    public function deviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    public function attemptCount(): int
    {
        return $this->attemptCount;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'event' => 'login_blocked',
            'username' => $this->username,
            'audience' => $this->audience,
            'block_reason' => $this->blockReason,
            'attempt_count' => $this->attemptCount,
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
