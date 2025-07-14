<?php

namespace App\Domain\Agent\Models;

use App\Shared\Exceptions\ValidationException;
use DateTime;

final class AgentProfile
{
    private readonly int $id;

    private readonly int $agentId;

    private readonly ?string $fullName;

    private readonly ?string $address;

    private readonly ?string $city;

    private readonly string $country;

    private readonly ?DateTime $dateOfBirth;

    private readonly ?string $gender;

    private readonly ?string $businessName;

    private readonly ?string $businessRegistration;

    private readonly ?string $businessAddress;

    private readonly string $preferredLanguage;

    private readonly string $timezone;

    private readonly ?array $notificationPreferences;

    private readonly ?DateTime $lastLoginAt;

    private readonly ?string $lastLoginIp;

    private readonly ?array $loginHistory;

    private readonly ?array $metadata;

    private readonly DateTime $createdAt;

    private readonly DateTime $updatedAt;

    public function __construct(
        int $id,
        int $agentId,
        ?string $fullName = null,
        ?string $address = null,
        ?string $city = null,
        string $country = 'Cambodia',
        ?DateTime $dateOfBirth = null,
        ?string $gender = null,
        ?string $businessName = null,
        ?string $businessRegistration = null,
        ?string $businessAddress = null,
        string $preferredLanguage = 'km',
        string $timezone = 'Asia/Phnom_Penh',
        ?array $notificationPreferences = null,
        ?DateTime $lastLoginAt = null,
        ?string $lastLoginIp = null,
        ?array $loginHistory = null,
        ?array $metadata = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->validateGender($gender);
        $this->validateCountry($country);
        $this->validateLanguage($preferredLanguage);
        $this->validateTimezone($timezone);
        $this->validateDateOfBirth($dateOfBirth);
        $this->validateLoginIp($lastLoginIp);

        $this->id = $id;
        $this->agentId = $agentId;
        $this->fullName = $fullName;
        $this->address = $address;
        $this->city = $city;
        $this->country = $country;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->businessName = $businessName;
        $this->businessRegistration = $businessRegistration;
        $this->businessAddress = $businessAddress;
        $this->preferredLanguage = $preferredLanguage;
        $this->timezone = $timezone;
        $this->notificationPreferences = $notificationPreferences;
        $this->lastLoginAt = $lastLoginAt;
        $this->lastLoginIp = $lastLoginIp;
        $this->loginHistory = $loginHistory;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new DateTime;
        $this->updatedAt = $updatedAt ?? new DateTime;
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

    public function dateOfBirth(): ?DateTime
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

    public function lastLoginAt(): ?DateTime
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

    public function createdAt(): DateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function hasCompleteProfile(): bool
    {
        return $this->fullName !== null
            && $this->address !== null
            && $this->city !== null
            && $this->dateOfBirth !== null;
    }

    public function hasBusiness(): bool
    {
        return $this->businessName !== null || $this->businessRegistration !== null;
    }

    public function getAge(): ?int
    {
        if ($this->dateOfBirth === null) {
            return null;
        }

        $now = new DateTime;
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
        if ($this->lastLoginAt === null) {
            return false;
        }

        $threshold = new DateTime('-30 days');

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
            'timestamp' => new DateTime,
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
            new DateTime,
            $ipAddress,
            $newHistory,
            $this->metadata,
            $this->createdAt,
            new DateTime
        );
    }

    public function updatePersonalInfo(
        ?string $fullName = null,
        ?string $address = null,
        ?string $city = null,
        ?string $country = null,
        ?DateTime $dateOfBirth = null,
        ?string $gender = null
    ): self {
        if ($country !== null) {
            $this->validateCountry($country);
        }
        if ($gender !== null) {
            $this->validateGender($gender);
        }
        if ($dateOfBirth !== null) {
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
            new DateTime
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
            new DateTime
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
            new DateTime
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
        if (empty($country)) {
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

    private function validateDateOfBirth(?DateTime $dateOfBirth): void
    {
        if ($dateOfBirth === null) {
            return;
        }

        $now = new DateTime;
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
