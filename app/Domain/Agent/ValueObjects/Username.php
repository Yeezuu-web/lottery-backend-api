<?php

declare(strict_types=1);

namespace App\Domain\Agent\ValueObjects;

use App\Shared\Exceptions\ValidationException;
use Illuminate\Support\Facades\DB;

final readonly class Username
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = mb_strtoupper(mb_trim($value));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Generate next available username for a given parent
     */
    public static function generateNextUsername(AgentType $agentType, ?self $parentUsername = null): self
    {
        $prefix = $parentUsername instanceof self ? $parentUsername->value() : '';

        return match ($agentType->value()) {
            AgentType::COMPANY => new self(self::generateNextCompanyUsername()),
            AgentType::SUPER_SENIOR => new self($prefix.'A'), // Start with A
            AgentType::SENIOR => new self($prefix.'AA'), // Start with AA
            AgentType::MASTER => new self($prefix.'AA'), // Start with AA
            AgentType::AGENT => new self($prefix.'AA'), // Start with AA
            AgentType::MEMBER => new self($prefix.'000'), // Start with 000
            default => throw new ValidationException('Cannot generate username for agent type: '.$agentType->value())
        };
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get the expected agent type based on username length
     */
    public function getAgentTypeFromLength(): AgentType
    {
        return match (mb_strlen($this->value)) {
            1 => new AgentType(AgentType::COMPANY),
            2 => new AgentType(AgentType::SUPER_SENIOR),
            4 => new AgentType(AgentType::SENIOR),
            6 => new AgentType(AgentType::MASTER),
            8 => new AgentType(AgentType::AGENT),
            11 => new AgentType(AgentType::MEMBER), // 8 chars + 3 digits
            default => throw new ValidationException('Invalid username length: '.mb_strlen($this->value))
        };
    }

    /**
     * Check if username is valid for given agent type
     */
    public function isValidForAgentType(AgentType $agentType): bool
    {
        return match ($agentType->value()) {
            AgentType::COMPANY => $this->isValidCompanyUsername(),
            AgentType::SUPER_SENIOR => $this->isValidSuperSeniorUsername(),
            AgentType::SENIOR => $this->isValidSeniorUsername(),
            AgentType::MASTER => $this->isValidMasterUsername(),
            AgentType::AGENT => $this->isValidAgentUsername(),
            AgentType::MEMBER => $this->isValidMemberUsername(),
            default => false
        };
    }

    /**
     * Get the parent username (upline identifier)
     */
    public function getParentUsername(): ?string
    {
        return match (mb_strlen($this->value)) {
            1 => null, // Company has no parent
            2 => mb_substr($this->value, 0, 1), // Super Senior -> Company
            4 => mb_substr($this->value, 0, 2), // Senior -> Super Senior
            6 => mb_substr($this->value, 0, 4), // Master -> Senior
            8 => mb_substr($this->value, 0, 6), // Agent -> Master
            11 => mb_substr($this->value, 0, 8), // Member -> Agent
            default => null
        };
    }

    /**
     * Check if this username is a child of given parent username
     */
    public function isChildOf(self $parentUsername): bool
    {
        $expectedParent = $this->getParentUsername();

        if ($expectedParent === null) {
            return false; // Company has no parent
        }

        return $expectedParent === $parentUsername->value();
    }

    /**
     * Generate next available company username
     */
    private static function generateNextCompanyUsername(): string
    {
        $used = DB::table('agents')
            ->whereRaw('LENGTH(username) = 1') // Only single-letter usernames
            ->pluck('username')
            ->map(fn ($u) => mb_strtoupper((string) $u))
            ->toArray();

        foreach (range('A', 'Z') as $letter) {
            if (! in_array($letter, $used)) {
                return $letter;
            }
        }

        throw new ValidationException('No available company usernames left (A-Z)', 422);
    }

    /**
     * Validate company username (1 char, A-Z)
     */
    private function isValidCompanyUsername(): bool
    {
        return mb_strlen($this->value) === 1 && preg_match('/^[A-Z]$/', $this->value);
    }

    /**
     * Validate super senior username (2 chars, AA-ZZ)
     */
    private function isValidSuperSeniorUsername(): bool
    {
        return mb_strlen($this->value) === 2 && preg_match('/^[A-Z]{2}$/', $this->value);
    }

    /**
     * Validate senior username (4 chars, AAAA-ZZZZ)
     */
    private function isValidSeniorUsername(): bool
    {
        return mb_strlen($this->value) === 4 && preg_match('/^[A-Z]{4}$/', $this->value);
    }

    /**
     * Validate master username (6 chars, AAAAAA-ZZZZZZ)
     */
    private function isValidMasterUsername(): bool
    {
        return mb_strlen($this->value) === 6 && preg_match('/^[A-Z]{6}$/', $this->value);
    }

    /**
     * Validate agent username (8 chars, AAAAAAAA-ZZZZZZZZ)
     */
    private function isValidAgentUsername(): bool
    {
        return mb_strlen($this->value) === 8 && preg_match('/^[A-Z]{8}$/', $this->value);
    }

    /**
     * Validate member username (8 chars + 3 digits, AAAAAAAA000-ZZZZZZZZ999)
     */
    private function isValidMemberUsername(): bool
    {
        return mb_strlen($this->value) === 11 && preg_match('/^[A-Z]{8}\d{3}$/', $this->value);
    }

    /**
     * Basic validation
     */
    private function validate(string $value): void
    {
        if ($value === '' || $value === '0') {
            throw new ValidationException('Username cannot be empty');
        }

        if (in_array(preg_match('/^[A-Za-z0-9]+$/', $value), [0, false], true)) {
            throw new ValidationException('Username can only contain letters and numbers');
        }

        $length = mb_strlen($value);
        $validLengths = [1, 2, 4, 6, 8, 11];

        if (! in_array($length, $validLengths, true)) {
            throw new ValidationException('Invalid username length. Must be 1, 2, 4, 6, 8, or 11 characters');
        }
    }
}
