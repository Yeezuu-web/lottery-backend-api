<?php

namespace App\Domain\Order\ValueObjects;

use App\Shared\Exceptions\ValidationException;

final readonly class OrderNumber
{
    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        $timestamp = date('YmdHis');
        $random = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return new self("ORD-{$timestamp}-{$random}");
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(OrderNumber $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (empty($this->value)) {
            throw new ValidationException('Order number cannot be empty');
        }

        if (strlen($this->value) < 10) {
            throw new ValidationException('Order number must be at least 10 characters long');
        }

        // Validate format: ORD-YYYYMMDDHHMMSS-XXXXXX
        if (! preg_match('/^ORD-\d{14}-\d{6}$/', $this->value)) {
            throw new ValidationException('Order number must follow format: ORD-YYYYMMDDHHMMSS-XXXXXX', 422);
        }
    }
}
