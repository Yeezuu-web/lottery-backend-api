<?php

namespace App\Domain\Order\Exceptions;

use App\Shared\Exceptions\DomainException;

final class CartException extends DomainException
{
    public static function empty(): self
    {
        return new self('Cart is empty');
    }

    public static function itemNotFound(int $itemId): self
    {
        return new self("Cart item {$itemId} not found");
    }

    public static function duplicateItem(): self
    {
        return new self('Item already exists in cart');
    }

    public static function invalidItem(string $message): self
    {
        return new self("Invalid cart item: {$message}");
    }

    public static function exceedsMaxItems(int $current, int $max): self
    {
        return new self("Cart exceeds maximum items. Current: {$current}, Max: {$max}");
    }

    public static function exceedsMaxAmount(float $current, float $max): self
    {
        return new self("Cart exceeds maximum amount. Current: {$current}, Max: {$max}");
    }

    public static function invalidAgent(int $agentId): self
    {
        return new self("Invalid agent ID: {$agentId}");
    }

    public static function submissionFailed(string $reason): self
    {
        return new self("Cart submission failed: {$reason}");
    }

    public static function processingError(string $message): self
    {
        return new self("Cart processing error: {$message}");
    }
}
