<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Auth\ValueObjects\DeviceInfo;
use DateTimeImmutable;

final readonly class LoginFailed
{
    public function __construct(
        private string $username,
        private string $audience,
        private string $failureReason,
        private DeviceInfo $deviceInfo,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(string $username, string $audience, string $failureReason, DeviceInfo $deviceInfo): self
    {
        return new self($username, $audience, $failureReason, $deviceInfo, new DateTimeImmutable());
    }

    public function username(): string
    {
        return $this->username;
    }

    public function audience(): string
    {
        return $this->audience;
    }

    public function failureReason(): string
    {
        return $this->failureReason;
    }

    public function deviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'event' => 'login_failed',
            'username' => $this->username,
            'audience' => $this->audience,
            'failure_reason' => $this->failureReason,
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
