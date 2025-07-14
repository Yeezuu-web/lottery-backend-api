<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class PayoutProfile implements Stringable
{
    private array $multipliers;

    public function __construct(array $multipliers)
    {
        $this->validateMultipliers($multipliers);
        $this->multipliers = $multipliers;
    }

    public function __toString(): string
    {
        return sprintf('PayoutProfile(2D: %s, 3D: %s)', $this->getMultiplier('2D'), $this->getMultiplier('3D'));
    }

    public static function fromArray(array $multipliers): self
    {
        return new self($multipliers);
    }

    public static function default(): self
    {
        return new self(['2D' => 90, '3D' => 800]);
    }

    public static function conservative(): self
    {
        return new self(['2D' => 70, '3D' => 600]);
    }

    public static function aggressive(): self
    {
        return new self(['2D' => 95, '3D' => 900]);
    }

    public function getMultiplier(string $gameType): float
    {
        return $this->multipliers[$gameType] ?? 0.0;
    }

    public function toArray(): array
    {
        return $this->multipliers;
    }

    public function toJson(): string
    {
        return json_encode($this->multipliers);
    }

    public function isConservative(): bool
    {
        return $this->getMultiplier('2D') <= 70 && $this->getMultiplier('3D') <= 600;
    }

    public function isDefault(): bool
    {
        return $this->getMultiplier('2D') === 90.0 && $this->getMultiplier('3D') === 800.0;
    }

    public function isAggressive(): bool
    {
        return $this->getMultiplier('2D') >= 95 && $this->getMultiplier('3D') >= 900;
    }

    public function getMaxCommissionSharingRate(): float
    {
        if ($this->isConservative()) {
            return 25.0;
        }

        if ($this->isAggressive()) {
            return 60.0;
        }

        return 50.0; // Default
    }

    public function getRiskLevel(): string
    {
        if ($this->isConservative()) {
            return 'conservative';
        }

        if ($this->isAggressive()) {
            return 'aggressive';
        }

        return 'default';
    }

    public function equals(self $other): bool
    {
        return $this->multipliers === $other->multipliers;
    }

    private function validateMultipliers(array $multipliers): void
    {
        if ($multipliers === []) {
            throw new InvalidArgumentException('Payout profile cannot be empty');
        }

        $requiredGameTypes = ['2D', '3D'];
        foreach ($requiredGameTypes as $gameType) {
            if (! isset($multipliers[$gameType])) {
                throw new InvalidArgumentException('Missing multiplier for game type: '.$gameType);
            }

            $multiplier = $multipliers[$gameType];
            if (! is_numeric($multiplier) || $multiplier <= 0) {
                throw new InvalidArgumentException(sprintf('Invalid multiplier for %s: must be a positive number', $gameType));
            }

            if ($multiplier > 1000) {
                throw new InvalidArgumentException(sprintf('Multiplier for %s too high: maximum is 1000', $gameType));
            }
        }

        // Business rule validation
        if ($multipliers['2D'] > $multipliers['3D'] / 8) {
            throw new InvalidArgumentException('2D multiplier ratio to 3D multiplier is invalid');
        }
    }
}
