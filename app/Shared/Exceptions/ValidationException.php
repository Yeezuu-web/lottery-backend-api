<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

final class ValidationException extends DomainException
{
    public static function fieldRequired(string $field): self
    {
        return new self(sprintf("Field '%s' is required", $field));
    }

    public static function fieldInvalid(string $field, string $reason = ''): self
    {
        $message = sprintf("Field '%s' is invalid", $field);
        if ($reason !== '' && $reason !== '0') {
            $message .= ': '.$reason;
        }

        return new self($message);
    }

    public static function valueOutOfRange(string $field, $min, $max, $actual): self
    {
        return new self(sprintf("Field '%s' must be between %s and %s, got %s", $field, $min, $max, $actual));
    }

    public static function valueNotInList(string $field, array $allowedValues, $actual): self
    {
        $allowed = implode(', ', $allowedValues);

        return new self(sprintf("Field '%s' must be one of: %s, got %s", $field, $allowed, $actual));
    }

    public static function emptyArray(string $field): self
    {
        return new self(sprintf("Field '%s' cannot be empty", $field));
    }

    public static function invalidFormat(string $field, string $format): self
    {
        return new self(sprintf("Field '%s' must be in format: %s", $field, $format));
    }

    public static function tooShort(string $field, int $minLength): self
    {
        return new self(sprintf("Field '%s' must be at least %d characters long", $field, $minLength));
    }

    public static function tooLong(string $field, int $maxLength): self
    {
        return new self(sprintf("Field '%s' must be at most %d characters long", $field, $maxLength));
    }

    public static function notNumeric(string $field): self
    {
        return new self(sprintf("Field '%s' must be numeric", $field));
    }

    public static function notPositive(string $field): self
    {
        return new self(sprintf("Field '%s' must be positive", $field));
    }

    public static function notUnique(string $field, $value): self
    {
        return new self(sprintf("Field '%s' with value '%s' already exists", $field, $value));
    }

    public static function relationNotFound(string $relation, $id): self
    {
        return new self(sprintf("Related %s with ID '%s' not found", $relation, $id));
    }

    public static function businessRule(string $rule): self
    {
        return new self('Business rule violation: '.$rule);
    }
}
