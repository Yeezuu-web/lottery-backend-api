<?php

declare(strict_types=1);

namespace App\Domain\Agent\Models;

use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;

final readonly class AgentProfile
{
    private string $country;

    private ?DateTimeImmutable $dateOfBirth;

    private ?string $gender;

    private string $preferredLanguage;

    private string $timezone;

    private ?string $lastLoginIp;

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    public function __construct(
        private int $id,
        private int $agentId,
        private ?string $fullName = null,
        private ?string $address = null,
        private ?string $city = null,
        string $country = 'Cambodia',
        ?DateTimeImmutable $dateOfBirth = null,
        ?string $gender = null,
        private ?string $businessName = null,
        private ?string $businessRegistration = null,
        private ?string $businessAddress = null,
        string $preferredLanguage = 'km',
        string $timezone = 'Asia/Phnom_Penh',
        private ?array $notificationPreferences = null,
        private ?DateTimeImmutable $lastLoginAt = null,
        ?string $lastLoginIp = null,
        private ?array $loginHistory = null,
        private ?array $metadata = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->validateGender($gender);
        $this->validateCountry($country);
        $this->validateLanguage($preferredLanguage);
        $this->validateTimezone($timezone);
        $this->validateDateOfBirth($dateOfBirth);
        $this->validateLoginIp($lastLoginIp);
        $this->country = $country;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->preferredLanguage = $preferredLanguage;
        $this->timezone = $timezone;
        $this->lastLoginIp = $lastLoginIp;
        $this->createdAt = $createdAt ?? new DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function fullName(): ?string
    {
        return $this->fullName;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function dateOfBirth(): ?DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function gender(): ?string
    {
        return $this->gender;
    }

    public function businessName(): ?string
    {
        return $this->businessName;
    }

    public function businessRegistration(): ?string
    {
        return $this->businessRegistration;
    }

    public function businessAddress(): ?string
    {
        return $this->businessAddress;
    }

    public function preferredLanguage(): string
    {
        return $this->preferredLanguage;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function notificationPreferences(): ?array
    {
        return $this->notificationPreferences;
    }

    public function lastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function lastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function loginHistory(): ?array
    {
        return $this->loginHistory;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasCompleteProfile(): bool
    {
        return $this->fullName !== null
            && $this->address !== null
            && $this->city !== null
            && $this->dateOfBirth instanceof DateTimeImmutable;
    }

    public function hasBusiness(): bool
    {
        return $this->businessName !== null || $this->businessRegistration !== null;
    }

    public function getAge(): ?int
    {
        if (! $this->dateOfBirth instanceof DateTimeImmutable) {
            return null;
        }

        $now = new DateTimeImmutable;
        $age = $now->diff($this->dateOfBirth);

        return $age->y;
    }

    public function isAdult(): bool
    {
        $age = $this->getAge();

        return $age !== null && $age >= 18;
    }

    public function hasRecentLogin(): bool
    {
        if (! $this->lastLoginAt instanceof DateTimeImmutable) {
            return false;
        }

        $threshold = new DateTimeImmutable('-30 days');

        return $this->lastLoginAt > $threshold;
    }

    public function getPreferredNotificationChannels(): array
    {
        if ($this->notificationPreferences === null) {
            return ['email']; // Default
        }

        return $this->notificationPreferences['channels'] ?? ['email'];
    }

    public function wantsNotificationType(string $type): bool
    {
        if ($this->notificationPreferences === null) {
            return true; // Default to all notifications
        }

        return $this->notificationPreferences[$type] ?? true;
    }

    public function updateLastLogin(string $ipAddress): self
    {
        $this->validateLoginIp($ipAddress);

        // Update login history
        $newHistory = $this->loginHistory ?? [];
        $newHistory[] = [
            'timestamp' => new DateTimeImmutable,
            'ip' => $ipAddress,
        ];

        // Keep only last 10 logins
        if (count($newHistory) > 10) {
            $newHistory = array_slice($newHistory, -10);
        }

        return new self(
            $this->id,
            $this->agentId,
            $this->fullName,
            $this->address,
            $this->city,
            $this->country,
            $this->dateOfBirth,
            $this->gender,
            $this->businessName,
            $this->businessRegistration,
            $this->businessAddress,
            $this->preferredLanguage,
            $this->timezone,
            $this->notificationPreferences,
            new DateTimeImmutable,
            $ipAddress,
            $newHistory,
            $this->metadata,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function updatePersonalInfo(
        ?string $fullName = null,
        ?string $address = null,
        ?string $city = null,
        ?string $country = null,
        ?DateTimeImmutable $dateOfBirth = null,
        ?string $gender = null
    ): self {
        if ($country !== null) {
            $this->validateCountry($country);
        }

        if ($gender !== null) {
            $this->validateGender($gender);
        }

        if ($dateOfBirth instanceof DateTimeImmutable) {
            $this->validateDateOfBirth($dateOfBirth);
        }

        return new self(
            $this->id,
            $this->agentId,
            $fullName ?? $this->fullName,
            $address ?? $this->address,
            $city ?? $this->city,
            $country ?? $this->country,
            $dateOfBirth ?? $this->dateOfBirth,
            $gender ?? $this->gender,
            $this->businessName,
            $this->businessRegistration,
            $this->businessAddress,
            $this->preferredLanguage,
            $this->timezone,
            $this->notificationPreferences,
            $this->lastLoginAt,
            $this->lastLoginIp,
            $this->loginHistory,
            $this->metadata,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function updateBusinessInfo(
        ?string $businessName = null,
        ?string $businessRegistration = null,
        ?string $businessAddress = null
    ): self {
        return new self(
            $this->id,
            $this->agentId,
            $this->fullName,
            $this->address,
            $this->city,
            $this->country,
            $this->dateOfBirth,
            $this->gender,
            $businessName ?? $this->businessName,
            $businessRegistration ?? $this->businessRegistration,
            $businessAddress ?? $this->businessAddress,
            $this->preferredLanguage,
            $this->timezone,
            $this->notificationPreferences,
            $this->lastLoginAt,
            $this->lastLoginIp,
            $this->loginHistory,
            $this->metadata,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function updatePreferences(
        ?string $preferredLanguage = null,
        ?string $timezone = null,
        ?array $notificationPreferences = null
    ): self {
        if ($preferredLanguage !== null) {
            $this->validateLanguage($preferredLanguage);
        }

        if ($timezone !== null) {
            $this->validateTimezone($timezone);
        }

        return new self(
            $this->id,
            $this->agentId,
            $this->fullName,
            $this->address,
            $this->city,
            $this->country,
            $this->dateOfBirth,
            $this->gender,
            $this->businessName,
            $this->businessRegistration,
            $this->businessAddress,
            $preferredLanguage ?? $this->preferredLanguage,
            $timezone ?? $this->timezone,
            $notificationPreferences ?? $this->notificationPreferences,
            $this->lastLoginAt,
            $this->lastLoginIp,
            $this->loginHistory,
            $this->metadata,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    private function validateGender(?string $gender): void
    {
        if ($gender !== null && ! in_array($gender, ['male', 'female', 'other'], true)) {
            throw new ValidationException('Gender must be male, female, or other');
        }
    }

    private function validateCountry(string $country): void
    {
        if ($country === '' || $country === '0') {
            throw new ValidationException('Country cannot be empty');
        }
    }

    private function validateLanguage(string $language): void
    {
        $validLanguages = ['km', 'en', 'zh'];
        if (! in_array($language, $validLanguages, true)) {
            throw new ValidationException('Invalid language. Must be: '.implode(', ', $validLanguages));
        }
    }

    private function validateTimezone(string $timezone): void
    {
        $validTimezones = ['Asia/Phnom_Penh', 'Asia/Bangkok', 'Asia/Singapore', 'UTC'];
        if (! in_array($timezone, $validTimezones, true)) {
            throw new ValidationException('Invalid timezone. Must be: '.implode(', ', $validTimezones));
        }
    }

    private function validateDateOfBirth(?DateTimeImmutable $dateOfBirth): void
    {
        if (! $dateOfBirth instanceof DateTimeImmutable) {
            return;
        }

        $now = new DateTimeImmutable;
        if ($dateOfBirth > $now) {
            throw new ValidationException('Date of birth cannot be in the future');
        }

        $age = $now->diff($dateOfBirth)->y;
        if ($age > 120) {
            throw new ValidationException('Age cannot be more than 120 years');
        }
    }

    private function validateLoginIp(?string $ip): void
    {
        if ($ip !== null && ! filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new ValidationException('Invalid IP address format');
        }
    }
}
