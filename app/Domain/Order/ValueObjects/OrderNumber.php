<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObjects;

use App\Shared\Exceptions\ValidationException;
use Stringable;

final readonly class OrderNumber implements Stringable
{
    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        $timestamp = date('YmdHis');
        $random = mb_str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return new self(sprintf('ORD-%s-%s', $timestamp, $random));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(): void
    {
        if ($this->value === '' || $this->value === '0') {
            throw new ValidationException('Order number cannot be empty');
        }

        if (mb_strlen($this->value) < 10) {
            throw new ValidationException('Order number must be at least 10 characters long');
        }

        // Validate format: ORD-YYYYMMDDHHMMSS-XXXXXX
        if (in_array(preg_match('/^ORD-\d{14}-\d{6}$/', $this->value), [0, false], true)) {
            throw new ValidationException('Order number must follow format: ORD-YYYYMMDDHHMMSS-XXXXXX', 422);
        }
    }
}
