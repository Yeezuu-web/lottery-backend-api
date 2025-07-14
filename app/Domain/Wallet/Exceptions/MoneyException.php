<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exceptions;

use Exception;

final class MoneyException extends Exception
{
    public static function invalidCurrency(string $currency): self
    {
        return new self('Invalid currency: '.$currency);
    }

    public static function currencyMismatch(string $currency1, string $currency2): self
    {
        return new self(sprintf('Currency mismatch: %s and %s', $currency1, $currency2));
    }

    public static function divisionByZero(): self
    {
        return new self('Division by zero is not allowed');
    }

    public static function invalidAmount(float $amount): self
    {
        return new self('Invalid amount: '.$amount);
    }

    public static function negativeAmount(float $amount): self
    {
        return new self('Negative amount not allowed: '.$amount);
    }

    public static function exceedsMaximum(float $amount, float $maximum): self
    {
        return new self(sprintf('Amount %s exceeds maximum allowed: %s', $amount, $maximum));
    }

    public static function belowMinimum(float $amount, float $minimum): self
    {
        return new self(sprintf('Amount %s below minimum required: %s', $amount, $minimum));
    }

    public static function invalidPrecision(float $amount, int $precision): self
    {
        return new self(sprintf('Amount %s has invalid precision. Expected: %d decimal places', $amount, $precision));
    }

    public static function conversionFailed(string $fromCurrency, string $toCurrency): self
    {
        return new self(sprintf('Failed to convert from %s to %s', $fromCurrency, $toCurrency));
    }

    public static function unsupportedCurrency(string $currency): self
    {
        return new self('Unsupported currency: '.$currency);
    }

    public static function invalidFormat(string $value): self
    {
        return new self('Invalid money format: '.$value);
    }

    public static function overflow(): self
    {
        return new self('Money amount overflow');
    }

    public static function underflow(): self
    {
        return new self('Money amount underflow');
    }
}
