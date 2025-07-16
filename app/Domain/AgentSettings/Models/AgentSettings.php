<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\Models;

use App\Domain\AgentSettings\ValueObjects\DailyLimit;
use DateTimeImmutable;

final readonly class AgentSettings
{
    public function __construct(
        private int $agentId,
        private DailyLimit $dailyLimit,
        private float $maxCommission,
        private float $maxShare,
        private array $numberLimits,
        private array $blockedNumbers,
        private bool $isActive,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {}

    public static function create(
        int $agentId,
        DailyLimit $dailyLimit,
        float $maxCommission,
        float $maxShare,
        array $numberLimits,
        array $blockedNumbers = []
    ): self {
        return new self(
            $agentId,
            $dailyLimit,
            $maxCommission,
            $maxShare,
            $numberLimits,
            $blockedNumbers,
            true, // isActive
            new DateTimeImmutable,
            new DateTimeImmutable
        );
    }

    public function getAgentId(): int
    {
        return $this->agentId;
    }

    public function getDailyLimit(): DailyLimit
    {
        return $this->dailyLimit;
    }

    public function getMaxCommission(): float
    {
        return $this->maxCommission;
    }

    public function getMaxShare(): float
    {
        return $this->maxShare;
    }

    public function getNumberLimits(): array
    {
        return $this->numberLimits;
    }

    public function getNumberLimitsArray(): array
    {
        $result = [];
        foreach ($this->numberLimits as $numberLimit) {
            $result[$numberLimit->getGameType()][$numberLimit->getNumber()] = $numberLimit->getLimit();
        }

        return $result;
    }

    public function getBlockedNumbers(): array
    {
        return $this->blockedNumbers;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isNumberBlocked(string $number): bool
    {
        return in_array($number, $this->blockedNumbers);
    }

    public function updateDailyLimit(DailyLimit $dailyLimit): self
    {
        return new self(
            $this->agentId,
            $dailyLimit,
            $this->maxCommission,
            $this->maxShare,
            $this->numberLimits,
            $this->blockedNumbers,
            $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function updateMaxCommission(float $maxCommission): self
    {
        return new self(
            $this->agentId,
            $this->dailyLimit,
            $maxCommission,
            $this->maxShare,
            $this->numberLimits,
            $this->blockedNumbers,
            $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function updateMaxShare(float $maxShare): self
    {
        return new self(
            $this->agentId,
            $this->dailyLimit,
            $this->maxCommission,
            $maxShare,
            $this->numberLimits,
            $this->blockedNumbers,
            $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function updateNumberLimits(array $numberLimits): self
    {
        return new self(
            $this->agentId,
            $this->dailyLimit,
            $this->maxCommission,
            $this->maxShare,
            $numberLimits,
            $this->blockedNumbers,
            $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function updateBlockedNumbers(array $blockedNumbers): self
    {
        return new self(
            $this->agentId,
            $this->dailyLimit,
            $this->maxCommission,
            $this->maxShare,
            $this->numberLimits,
            $blockedNumbers,
            $this->isActive,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function activate(): self
    {
        return new self(
            $this->agentId,
            $this->dailyLimit,
            $this->maxCommission,
            $this->maxShare,
            $this->numberLimits,
            $this->blockedNumbers,
            true,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function deactivate(): self
    {
        return new self(
            $this->agentId,
            $this->dailyLimit,
            $this->maxCommission,
            $this->maxShare,
            $this->numberLimits,
            $this->blockedNumbers,
            false,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'daily_limit' => $this->dailyLimit->toArray(),
            'max_commission' => $this->maxCommission,
            'max_share' => $this->maxShare,
            'number_limits' => $this->getNumberLimitsArray(),
            'blocked_numbers' => $this->blockedNumbers,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
