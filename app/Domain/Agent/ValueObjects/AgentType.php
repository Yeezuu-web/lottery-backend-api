<?php

declare(strict_types=1);

namespace App\Domain\Agent\ValueObjects;

use App\Shared\Exceptions\ValidationException;

final class AgentType
{
    public const COMPANY = 'company';

    public const SUPER_SENIOR = 'super senior';

    public const SENIOR = 'senior';

    public const MASTER = 'master';

    public const AGENT = 'agent';

    public const MEMBER = 'member';

    private readonly string $value;

    private static array $validTypes = [
        self::COMPANY,
        self::SUPER_SENIOR,
        self::SENIOR,
        self::MASTER,
        self::AGENT,
        self::MEMBER,
    ];

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function member(): self
    {
        return new self(self::MEMBER);
    }

    public static function agent(): self
    {
        return new self(self::AGENT);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function canManageSubAgents(): bool
    {
        return in_array($this->value, [
            self::COMPANY,
            self::SUPER_SENIOR,
            self::SENIOR,
            self::MASTER,
            self::AGENT,
        ]);
    }

    public function getHierarchyLevel(): int
    {
        return match ($this->value) {
            self::COMPANY => 1,
            self::SUPER_SENIOR => 2,
            self::SENIOR => 3,
            self::MASTER => 4,
            self::AGENT => 5,
            self::MEMBER => 6,
            default => 0
        };
    }

    /**
     * Check if agent type is Company
     */
    public function isCompany(): bool
    {
        return $this->value === self::COMPANY;
    }

    /**
     * Check if agent type is Super Senior
     */
    public function isSuperSenior(): bool
    {
        return $this->value === self::SUPER_SENIOR;
    }

    /**
     * Check if agent type is Senior
     */
    public function isSenior(): bool
    {
        return $this->value === self::SENIOR;
    }

    /**
     * Check if agent type is Master
     */
    public function isMaster(): bool
    {
        return $this->value === self::MASTER;
    }

    /**
     * Check if agent type is Agent
     */
    public function isAgent(): bool
    {
        return $this->value === self::AGENT;
    }

    /**
     * Check if agent type is Member
     */
    public function isMember(): bool
    {
        return $this->value === self::MEMBER;
    }

    /**
     * Check if this agent type can access upline functionality
     */
    public function canAccessUpline(): bool
    {
        return ! $this->isMember();
    }

    /**
     * Check if this agent type can access member functionality
     */
    public function canAccessMember(): bool
    {
        return $this->isMember();
    }

    /**
     * Check if this agent type is in the management hierarchy
     */
    public function isInManagementHierarchy(): bool
    {
        return in_array($this->value, [
            self::COMPANY,
            self::SUPER_SENIOR,
            self::SENIOR,
            self::MASTER,
            self::AGENT,
        ]);
    }

    /**
     * Check if this agent type can place bets member only
     */
    public function canPlaceBets(): bool
    {
        return $this->value === self::MEMBER;
    }

    /**
     * Get display name for agent type
     */
    public function getDisplayName(): string
    {
        return match ($this->value) {
            self::COMPANY => 'Company',
            self::SUPER_SENIOR => 'Super Senior',
            self::SENIOR => 'Senior',
            self::MASTER => 'Master',
            self::AGENT => 'Agent',
            self::MEMBER => 'Member',
        };
    }

    /**
     * Check if can manage specific agent type
     */
    public function canManage(self $otherType): bool
    {
        return $this->getHierarchyLevel() < $otherType->getHierarchyLevel();
    }

    private function validate(string $value): void
    {
        if (! in_array($value, self::$validTypes, true)) {
            throw new ValidationException('Invalid agent type: '.$value);
        }
    }
}
