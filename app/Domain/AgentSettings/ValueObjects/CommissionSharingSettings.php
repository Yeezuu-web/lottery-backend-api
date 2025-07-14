<?php

namespace App\Domain\AgentSettings\ValueObjects;

use InvalidArgumentException;

final class CommissionSharingSettings
{
    private readonly ?CommissionRate $commissionRate;

    private readonly ?SharingRate $sharingRate;

    private readonly float $maxCombinedRate;

    public function __construct(
        ?CommissionRate $commissionRate = null,
        ?SharingRate $sharingRate = null,
        float $maxCombinedRate = 50.0
    ) {
        $this->validateSettings($commissionRate, $sharingRate, $maxCombinedRate);
        $this->commissionRate = $commissionRate;
        $this->sharingRate = $sharingRate;
        $this->maxCombinedRate = $maxCombinedRate;
    }

    public static function commissionOnly(float $commissionRate, float $maxCombinedRate = 50.0): self
    {
        return new self(
            CommissionRate::fromPercentage($commissionRate),
            null,
            $maxCombinedRate
        );
    }

    public static function sharingOnly(float $sharingRate, float $maxCombinedRate = 50.0): self
    {
        return new self(
            null,
            SharingRate::fromPercentage($sharingRate),
            $maxCombinedRate
        );
    }

    public static function both(float $commissionRate, float $sharingRate, float $maxCombinedRate = 50.0): self
    {
        return new self(
            CommissionRate::fromPercentage($commissionRate),
            SharingRate::fromPercentage($sharingRate),
            $maxCombinedRate
        );
    }

    public static function fromPayoutProfile(
        ?float $commissionRate,
        ?float $sharingRate,
        PayoutProfile $payoutProfile
    ): self {
        $commission = $commissionRate !== null ? CommissionRate::fromPercentage($commissionRate) : null;
        $sharing = $sharingRate !== null ? SharingRate::fromPercentage($sharingRate) : null;

        // If both are null, create default with zero values
        if ($commission === null && $sharing === null) {
            $commission = CommissionRate::zero();
            $sharing = SharingRate::zero();
        }

        return new self(
            $commission,
            $sharing,
            $payoutProfile->getMaxCommissionSharingRate()
        );
    }

    public static function default(): self
    {
        return new self(
            CommissionRate::default(),
            SharingRate::default(),
            50.0
        );
    }

    public static function none(): self
    {
        return new self(
            CommissionRate::zero(),
            SharingRate::zero(),
            50.0
        );
    }

    public function getCommissionRate(): ?CommissionRate
    {
        return $this->commissionRate;
    }

    public function getSharingRate(): ?SharingRate
    {
        return $this->sharingRate;
    }

    public function getMaxCombinedRate(): float
    {
        return $this->maxCombinedRate;
    }

    public function hasCommission(): bool
    {
        return $this->commissionRate !== null && ! $this->commissionRate->isZero();
    }

    public function hasSharing(): bool
    {
        return $this->sharingRate !== null && ! $this->sharingRate->isZero();
    }

    public function hasEither(): bool
    {
        return $this->hasCommission() || $this->hasSharing();
    }

    public function hasBoth(): bool
    {
        return $this->hasCommission() && $this->hasSharing();
    }

    public function getCommissionRateValue(): float
    {
        return $this->commissionRate?->getRate() ?? 0.0;
    }

    public function getSharingRateValue(): float
    {
        return $this->sharingRate?->getRate() ?? 0.0;
    }

    public function getTotalRate(): float
    {
        return $this->getCommissionRateValue() + $this->getSharingRateValue();
    }

    public function getRemainingCapacity(): float
    {
        return $this->maxCombinedRate - $this->getTotalRate();
    }

    public function canAddCommission(float $additionalCommission): bool
    {
        $currentTotal = $this->getTotalRate();

        return ($currentTotal + $additionalCommission) <= $this->maxCombinedRate;
    }

    public function canAddSharing(float $additionalSharing): bool
    {
        $currentTotal = $this->getTotalRate();

        return ($currentTotal + $additionalSharing) <= $this->maxCombinedRate;
    }

    public function isWithinLimits(): bool
    {
        return $this->getTotalRate() <= $this->maxCombinedRate;
    }

    public function withCommissionRate(?float $newCommissionRate): self
    {
        $commission = $newCommissionRate !== null ? CommissionRate::fromPercentage($newCommissionRate) : null;

        return new self($commission, $this->sharingRate, $this->maxCombinedRate);
    }

    public function withSharingRate(?float $newSharingRate): self
    {
        $sharing = $newSharingRate !== null ? SharingRate::fromPercentage($newSharingRate) : null;

        return new self($this->commissionRate, $sharing, $this->maxCombinedRate);
    }

    public function withMaxCombinedRate(float $newMaxCombinedRate): self
    {
        return new self($this->commissionRate, $this->sharingRate, $newMaxCombinedRate);
    }

    public function calculateCommissionAmount(float $turnover): float
    {
        return $this->commissionRate?->calculateAmount($turnover) ?? 0.0;
    }

    public function calculateSharingAmount(float $turnover): float
    {
        return $this->sharingRate?->calculateAmount($turnover) ?? 0.0;
    }

    public function calculateTotalAmount(float $turnover): float
    {
        return $this->calculateCommissionAmount($turnover) + $this->calculateSharingAmount($turnover);
    }

    public function toArray(): array
    {
        return [
            'commission' => $this->commissionRate?->toArray(),
            'sharing' => $this->sharingRate?->toArray(),
            'max_combined_rate' => $this->maxCombinedRate,
            'total_rate' => $this->getTotalRate(),
            'remaining_capacity' => $this->getRemainingCapacity(),
            'has_commission' => $this->hasCommission(),
            'has_sharing' => $this->hasSharing(),
            'has_either' => $this->hasEither(),
            'has_both' => $this->hasBoth(),
        ];
    }

    public function equals(CommissionSharingSettings $other): bool
    {
        $commissionEqual = ($this->commissionRate === null && $other->commissionRate === null) ||
                          ($this->commissionRate !== null && $other->commissionRate !== null &&
                           $this->commissionRate->equals($other->commissionRate));

        $sharingEqual = ($this->sharingRate === null && $other->sharingRate === null) ||
                       ($this->sharingRate !== null && $other->sharingRate !== null &&
                        $this->sharingRate->equals($other->sharingRate));

        return $commissionEqual && $sharingEqual && $this->maxCombinedRate === $other->maxCombinedRate;
    }

    private function validateSettings(?CommissionRate $commissionRate, ?SharingRate $sharingRate, float $maxCombinedRate): void
    {
        if ($maxCombinedRate < 0 || $maxCombinedRate > 100) {
            throw new InvalidArgumentException('Max combined rate must be between 0 and 100');
        }

        // Check if total rate exceeds maximum
        $totalRate = ($commissionRate?->getRate() ?? 0.0) + ($sharingRate?->getRate() ?? 0.0);
        if ($totalRate > $maxCombinedRate) {
            throw new InvalidArgumentException(
                sprintf(
                    'Total commission and sharing rate (%.2f%%) exceeds maximum allowed (%.2f%%)',
                    $totalRate,
                    $maxCombinedRate
                )
            );
        }

        // Allow both to be null (will be handled by fromPayoutProfile method)
        // This enables flexible scenarios where agents can have:
        // - Only commission (sharing = null or 0)
        // - Only sharing (commission = null or 0)
        // - Both commission and sharing
        // - Neither (both = null or 0)
    }

    public function __toString(): string
    {
        $parts = [];

        if ($this->hasCommission()) {
            $parts[] = sprintf('commission: %.2f%%', $this->getCommissionRateValue());
        }

        if ($this->hasSharing()) {
            $parts[] = sprintf('sharing: %.2f%%', $this->getSharingRateValue());
        }

        if (empty($parts)) {
            $parts[] = 'none';
        }

        return sprintf(
            'CommissionSharingSettings(%s, total: %.2f%%, max: %.2f%%)',
            implode(', ', $parts),
            $this->getTotalRate(),
            $this->maxCombinedRate
        );
    }
}
