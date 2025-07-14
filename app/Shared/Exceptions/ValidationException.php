<?php

namespace App\Shared\Exceptions;

final class ValidationException extends DomainException
{
    public static function fieldRequired(string $field): self
    {
        return new self("Field '{$field}' is required");
    }

    public static function fieldInvalid(string $field, string $reason = ''): self
    {
        $message = "Field '{$field}' is invalid";
        if (! empty($reason)) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }

    public static function valueOutOfRange(string $field, $min, $max, $actual): self
    {
        return new self("Field '{$field}' must be between {$min} and {$max}, got {$actual}");
    }

    public static function valueNotInList(string $field, array $allowedValues, $actual): self
    {
        $allowed = implode(', ', $allowedValues);

        return new self("Field '{$field}' must be one of: {$allowed}, got {$actual}");
    }

    public static function emptyArray(string $field): self
    {
        return new self("Field '{$field}' cannot be empty");
    }

    public static function invalidFormat(string $field, string $format): self
    {
        return new self("Field '{$field}' must be in format: {$format}");
    }

    public static function tooShort(string $field, int $minLength): self
    {
        return new self("Field '{$field}' must be at least {$minLength} characters long");
    }

    public static function tooLong(string $field, int $maxLength): self
    {
        return new self("Field '{$field}' must be at most {$maxLength} characters long");
    }

    public static function notNumeric(string $field): self
    {
        return new self("Field '{$field}' must be numeric");
    }

    public static function notPositive(string $field): self
    {
        return new self("Field '{$field}' must be positive");
    }

    public static function notUnique(string $field, $value): self
    {
        return new self("Field '{$field}' with value '{$value}' already exists");
    }

    public static function relationNotFound(string $relation, $id): self
    {
        return new self("Related {$relation} with ID '{$id}' not found");
    }

    public static function businessRule(string $rule): self
    {
        return new self("Business rule violation: {$rule}");
    }
}
