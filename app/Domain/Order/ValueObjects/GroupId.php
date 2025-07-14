<?php

namespace App\Domain\Order\ValueObjects;

use App\Shared\Exceptions\ValidationException;

final readonly class GroupId
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

        return new self("GRP-{$timestamp}-{$random}");
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(GroupId $other): bool
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
            throw new ValidationException('Group ID cannot be empty');
        }

        if (strlen($this->value) < 10) {
            throw new ValidationException('Group ID must be at least 10 characters long');
        }

        // Validate format: GRP-YYYYMMDDHHMMSS-XXXXXX
        if (! preg_match('/^GRP-\d{14}-\d{6}$/', $this->value)) {
            throw new ValidationException('Group ID must follow format: GRP-YYYYMMDDHHMMSS-XXXXXX');
        }
    }
}
