<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\ValueObjects;

use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\Wallet\ValueObjects\Money;

final readonly class DailyLimit
{
    public function __construct(
        private ?Money $limit
    ) {
        if ($this->limit && $this->limit->isNegative()) {
            throw AgentSettingsException::invalidDailyLimit($this->limit->amount());
        }
    }

    public static function unlimited(): self
    {
        return new self(null);
    }

    public static function fromAmount(float $amount): self
    {
        return new self(Money::fromAmount($amount));
    }

    public static function create(?int $limit): self
    {
        return $limit === null ? self::unlimited() : self::fromAmount($limit);
    }

    public function isUnlimited(): bool
    {
        return ! $this->limit instanceof Money;
    }

    public function hasLimit(): bool
    {
        return $this->limit instanceof Money;
    }

    public function getLimit(): ?int
    {
        return $this->limit instanceof Money ? (int) $this->limit->amount() : null;
    }

    public function isExceeded(Money $currentUsage): bool
    {
        if (! $this->hasLimit()) {
            return false;
        }

        return $currentUsage->isGreaterThan($this->limit);
    }

    public function remainingAmount(Money $currentUsage): Money
    {
        if (! $this->hasLimit()) {
            return Money::fromAmount(PHP_FLOAT_MAX);
        }

        $remaining = $this->limit->subtract($currentUsage);

        return $remaining->isNegative() ? Money::zero() : $remaining;
    }

    public function toArray(): array
    {
        return [
            'limit' => $this->limit instanceof Money ? (int) $this->limit->amount() : null,
            'has_limit' => $this->hasLimit(),
        ];
    }
}
