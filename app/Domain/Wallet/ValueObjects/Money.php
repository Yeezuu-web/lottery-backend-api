<?php

namespace App\Domain\Wallet\ValueObjects;

use App\Shared\Exceptions\ValidationException;

final class Money
{
    private function __construct(
        private float $amount,
        private string $currency
    ) {
        $this->validate();
    }

    public static function fromAmount(float $amount, string $currency = 'KHR'): self
    {
        return new self($amount, $currency);
    }

    public static function zero(string $currency = 'KHR'): self
    {
        return new self(0.0, $currency);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function divide(float $divisor): self
    {
        if ($divisor === 0.0) {
            throw new ValidationException('Cannot divide by zero');
        }

        return new self($this->amount / $divisor, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function isGreaterThanOrEqual(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount >= $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount < $other->amount;
    }

    public function isLessThanOrEqual(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount <= $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0.0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0.0;
    }

    public function toString(): string
    {
        return number_format($this->amount, 2).' '.$this->currency;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new ValidationException(
                "Cannot operate on different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }

    private function validate(): void
    {
        if (empty($this->currency)) {
            throw new ValidationException('Currency cannot be empty');
        }

        if (strlen($this->currency) !== 3) {
            throw new ValidationException('Currency must be a 3-character code');
        }

        if (! is_numeric($this->amount)) {
            throw new ValidationException('Amount must be numeric');
        }
    }

    public function isSameCurrency(Money $other): bool
    {
        return $this->currency === $other->currency;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
