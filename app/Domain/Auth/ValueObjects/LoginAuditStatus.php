<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObjects;

use App\Shared\Exceptions\ValidationException;
use Stringable;

final readonly class LoginAuditStatus implements Stringable
{
    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    public const LOCKED = 'locked';

    public const EXPIRED = 'expired';

    public const BLOCKED = 'blocked';

    public const SUSPICIOUS = 'suspicious';

    private function __construct(private string $value)
    {
        $this->validate();
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function success(): self
    {
        return new self(self::SUCCESS);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public static function locked(): self
    {
        return new self(self::LOCKED);
    }

    public static function expired(): self
    {
        return new self(self::EXPIRED);
    }

    public static function blocked(): self
    {
        return new self(self::BLOCKED);
    }

    public static function suspicious(): self
    {
        return new self(self::SUSPICIOUS);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isSuccess(): bool
    {
        return $this->value === self::SUCCESS;
    }

    public function isFailure(): bool
    {
        return in_array($this->value, [self::FAILED, self::LOCKED, self::EXPIRED, self::BLOCKED, self::SUSPICIOUS], true);
    }

    public function isSecurityConcern(): bool
    {
        return in_array($this->value, [self::BLOCKED, self::SUSPICIOUS], true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(): void
    {
        $validStatuses = [
            self::SUCCESS,
            self::FAILED,
            self::LOCKED,
            self::EXPIRED,
            self::BLOCKED,
            self::SUSPICIOUS,
        ];

        if (! in_array($this->value, $validStatuses, true)) {
            throw new ValidationException('Invalid login audit status: '.$this->value);
        }
    }
}
