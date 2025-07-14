<?php

declare(strict_types=1);

namespace App\Domain\Agent\Models;

use App\Domain\Wallet\ValueObjects\Money;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;

final readonly class AgentSettings
{
    private float $commissionRate;

    private float $maxPayoutRate;

    private ?array $blockedNumbers;

    private ?array $allowedChannels;

    private ?array $allowedProvinces;

    private ?Money $dailyLimit;

    private ?Money $monthlyLimit;

    private ?int $maxBetsPerDraw;

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    public function __construct(
        private int $id,
        private int $agentId,
        float $commissionRate = 0.00,
        float $maxPayoutRate = 0.00,
        private ?array $payoutRates = null,
        ?array $blockedNumbers = null,
        private ?array $bettingLimits = null,
        ?array $allowedChannels = null,
        ?array $allowedProvinces = null,
        private ?array $operatingHours = null,
        private ?array $restrictedPeriods = null,
        private bool $canPlaceBets = true,
        private bool $canViewReports = true,
        private bool $canManageSubAgents = false,
        private bool $autoSettlement = false,
        ?Money $dailyLimit = null,
        ?Money $monthlyLimit = null,
        ?int $maxBetsPerDraw = null,
        private ?array $customSettings = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->validateCommissionRate($commissionRate);
        $this->validateMaxPayoutRate($maxPayoutRate);
        $this->validateBlockedNumbers($blockedNumbers);
        $this->validateChannels($allowedChannels);
        $this->validateProvinces($allowedProvinces);
        $this->validateLimits($dailyLimit, $monthlyLimit);
        $this->validateMaxBetsPerDraw($maxBetsPerDraw);
        $this->commissionRate = $commissionRate;
        $this->maxPayoutRate = $maxPayoutRate;
        $this->blockedNumbers = $blockedNumbers;
        $this->allowedChannels = $allowedChannels;
        $this->allowedProvinces = $allowedProvinces;
        $this->dailyLimit = $dailyLimit;
        $this->monthlyLimit = $monthlyLimit;
        $this->maxBetsPerDraw = $maxBetsPerDraw;
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

    public function commissionRate(): float
    {
        return $this->commissionRate;
    }

    public function maxPayoutRate(): float
    {
        return $this->maxPayoutRate;
    }

    public function payoutRates(): ?array
    {
        return $this->payoutRates;
    }

    public function blockedNumbers(): ?array
    {
        return $this->blockedNumbers;
    }

    public function bettingLimits(): ?array
    {
        return $this->bettingLimits;
    }

    public function allowedChannels(): ?array
    {
        return $this->allowedChannels;
    }

    public function allowedProvinces(): ?array
    {
        return $this->allowedProvinces;
    }

    public function operatingHours(): ?array
    {
        return $this->operatingHours;
    }

    public function restrictedPeriods(): ?array
    {
        return $this->restrictedPeriods;
    }

    public function canPlaceBets(): bool
    {
        return $this->canPlaceBets;
    }

    public function canViewReports(): bool
    {
        return $this->canViewReports;
    }

    public function canManageSubAgents(): bool
    {
        return $this->canManageSubAgents;
    }

    public function autoSettlement(): bool
    {
        return $this->autoSettlement;
    }

    public function dailyLimit(): ?Money
    {
        return $this->dailyLimit;
    }

    public function monthlyLimit(): ?Money
    {
        return $this->monthlyLimit;
    }

    public function maxBetsPerDraw(): ?int
    {
        return $this->maxBetsPerDraw;
    }

    public function customSettings(): ?array
    {
        return $this->customSettings;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isNumberBlocked(string $number): bool
    {
        return $this->blockedNumbers !== null && in_array($number, $this->blockedNumbers, true);
    }

    public function isChannelAllowed(string $channel): bool
    {
        return $this->allowedChannels === null || in_array($channel, $this->allowedChannels, true);
    }

    public function isProvinceAllowed(string $province): bool
    {
        return $this->allowedProvinces === null || in_array($province, $this->allowedProvinces, true);
    }

    public function hasReachedDailyLimit(Money $currentDailySpent): bool
    {
        if (! $this->dailyLimit instanceof Money) {
            return false;
        }

        if ($currentDailySpent->isGreaterThan($this->dailyLimit)) {
            return true;
        }

        return $currentDailySpent->equals($this->dailyLimit);
    }

    public function hasReachedMonthlyLimit(Money $currentMonthlySpent): bool
    {
        if (! $this->monthlyLimit instanceof Money) {
            return false;
        }

        if ($currentMonthlySpent->isGreaterThan($this->monthlyLimit)) {
            return true;
        }

        return $currentMonthlySpent->equals($this->monthlyLimit);
    }

    public function hasReachedMaxBetsPerDraw(int $currentBetsCount): bool
    {
        return $this->maxBetsPerDraw !== null && $currentBetsCount >= $this->maxBetsPerDraw;
    }

    public function calculateCommission(Money $amount): Money
    {
        return $amount->multiply($this->commissionRate / 100);
    }

    public function calculateMaxPayout(Money $betAmount): Money
    {
        return $betAmount->multiply($this->maxPayoutRate / 100);
    }

    public function getPayoutRateForBetType(string $betType): ?float
    {
        if ($this->payoutRates === null) {
            return null;
        }

        return $this->payoutRates[$betType] ?? null;
    }

    public function updateCommissionRate(float $newRate): self
    {
        $this->validateCommissionRate($newRate);

        return new self(
            $this->id,
            $this->agentId,
            $newRate,
            $this->maxPayoutRate,
            $this->payoutRates,
            $this->blockedNumbers,
            $this->bettingLimits,
            $this->allowedChannels,
            $this->allowedProvinces,
            $this->operatingHours,
            $this->restrictedPeriods,
            $this->canPlaceBets,
            $this->canViewReports,
            $this->canManageSubAgents,
            $this->autoSettlement,
            $this->dailyLimit,
            $this->monthlyLimit,
            $this->maxBetsPerDraw,
            $this->customSettings,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    public function enableBetting(): self
    {
        return $this->updateBettingPermission(true);
    }

    public function disableBetting(): self
    {
        return $this->updateBettingPermission(false);
    }

    private function updateBettingPermission(bool $canPlaceBets): self
    {
        return new self(
            $this->id,
            $this->agentId,
            $this->commissionRate,
            $this->maxPayoutRate,
            $this->payoutRates,
            $this->blockedNumbers,
            $this->bettingLimits,
            $this->allowedChannels,
            $this->allowedProvinces,
            $this->operatingHours,
            $this->restrictedPeriods,
            $canPlaceBets,
            $this->canViewReports,
            $this->canManageSubAgents,
            $this->autoSettlement,
            $this->dailyLimit,
            $this->monthlyLimit,
            $this->maxBetsPerDraw,
            $this->customSettings,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    private function validateCommissionRate(float $rate): void
    {
        if ($rate < 0 || $rate > 100) {
            throw new ValidationException('Commission rate must be between 0 and 100');
        }
    }

    private function validateMaxPayoutRate(float $rate): void
    {
        if ($rate < 0 || $rate > 10000) {
            throw new ValidationException('Max payout rate must be between 0 and 10000');
        }
    }

    private function validateBlockedNumbers(?array $numbers): void
    {
        if ($numbers === null) {
            return;
        }

        foreach ($numbers as $number) {
            if (! is_string($number) || in_array(preg_match('/^\d+$/', $number), [0, false], true)) {
                throw new ValidationException('Blocked numbers must be numeric strings');
            }
        }
    }

    private function validateChannels(?array $channels): void
    {
        if ($channels === null) {
            return;
        }

        $validChannels = ['A', 'B', 'C', 'D'];
        foreach ($channels as $channel) {
            if (! in_array($channel, $validChannels, true)) {
                throw new ValidationException('Invalid channel: '.$channel);
            }
        }
    }

    private function validateProvinces(?array $provinces): void
    {
        if ($provinces === null) {
            return;
        }

        $validProvinces = ['PP', 'SR', 'KP', 'BB'];
        foreach ($provinces as $province) {
            if (! in_array($province, $validProvinces, true)) {
                throw new ValidationException('Invalid province: '.$province);
            }
        }
    }

    private function validateLimits(?Money $dailyLimit, ?Money $monthlyLimit): void
    {
        if ($dailyLimit instanceof Money && $monthlyLimit instanceof Money && $dailyLimit->isGreaterThan($monthlyLimit)) {
            throw new ValidationException('Daily limit cannot be greater than monthly limit');
        }
    }

    private function validateMaxBetsPerDraw(?int $maxBets): void
    {
        if ($maxBets !== null && $maxBets < 1) {
            throw new ValidationException('Maximum bets per draw must be at least 1');
        }
    }
}
