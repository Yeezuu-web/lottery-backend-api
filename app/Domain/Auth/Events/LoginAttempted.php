<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Auth\ValueObjects\DeviceInfo;
use DateTimeImmutable;

final readonly class LoginAttempted
{
    public function __construct(
        private string $username,
        private string $audience,
        private DeviceInfo $deviceInfo,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(string $username, string $audience, DeviceInfo $deviceInfo): self
    {
        return new self($username, $audience, $deviceInfo, new DateTimeImmutable());
    }

    public function username(): string
    {
        return $this->username;
    }

    public function audience(): string
    {
        return $this->audience;
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
            'event' => 'login_attempted',
            'username' => $this->username,
            'audience' => $this->audience,
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
