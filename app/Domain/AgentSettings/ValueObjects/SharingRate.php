<?php

namespace App\Domain\AgentSettings\ValueObjects;

use InvalidArgumentException;

final class SharingRate
{
    private readonly float $rate;

    private readonly float $maxRate;

    public function __construct(float $rate, float $maxRate = 100.0)
    {
        $this->validateRate($rate, $maxRate);
        $this->rate = $rate;
        $this->maxRate = $maxRate;
    }

    public static function fromPercentage(float $percentage, float $maxRate = 100.0): self
    {
        return new self($percentage, $maxRate);
    }

    public static function fromFloat(float $value, float $maxRate = 100.0): self
    {
        return new self($value, $maxRate);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public static function default(): self
    {
        return new self(2.0);
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getMaxRate(): float
    {
        return $this->maxRate;
    }

    public function isZero(): bool
    {
        return $this->rate === 0.0;
    }

    public function isWithinLimit(float $limit): bool
    {
        return $this->rate <= $limit;
    }

    public function canIncrease(float $additionalRate): bool
    {
        return ($this->rate + $additionalRate) <= $this->maxRate;
    }

    public function withRate(float $newRate): self
    {
        return new self($newRate, $this->maxRate);
    }

    public function withMaxRate(float $newMaxRate): self
    {
        return new self($this->rate, $newMaxRate);
    }

    public function calculateAmount(float $turnover): float
    {
        return $turnover * ($this->rate / 100);
    }

    public function toArray(): array
    {
        return [
            'rate' => $this->rate,
            'max_rate' => $this->maxRate,
            'is_zero' => $this->isZero(),
        ];
    }

    public function equals(SharingRate $other): bool
    {
        return $this->rate === $other->rate &&
               $this->maxRate === $other->maxRate;
    }

    private function validateRate(float $rate, float $maxRate): void
    {
        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException('Sharing rate must be between 0 and 100');
        }

        if ($maxRate < 0 || $maxRate > 100) {
            throw new InvalidArgumentException('Max sharing rate must be between 0 and 100');
        }

        if ($rate > $maxRate) {
            throw new InvalidArgumentException(
                sprintf(
                    'Sharing rate (%.2f%%) exceeds maximum allowed (%.2f%%)',
                    $rate,
                    $maxRate
                )
            );
        }
    }

    public function __toString(): string
    {
        return sprintf('SharingRate(%.2f%%, max: %.2f%%)', $this->rate, $this->maxRate);
    }
}
