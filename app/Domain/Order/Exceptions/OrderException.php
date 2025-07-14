<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use App\Shared\Exceptions\DomainException;

final class OrderException extends DomainException
{
    public static function invalidBetData(string $message): self
    {
        return new self('Invalid bet data: '.$message);
    }

    public static function invalidOrderStatus(string $currentStatus, string $attemptedStatus): self
    {
        return new self(sprintf('Cannot change order status from %s to %s', $currentStatus, $attemptedStatus));
    }

    public static function orderAlreadyPrinted(string $orderNumber): self
    {
        return new self(sprintf('Order %s is already printed', $orderNumber));
    }

    public static function orderCannotBeCancelled(string $orderNumber, string $status): self
    {
        return new self(sprintf('Order %s cannot be cancelled in status: %s', $orderNumber, $status));
    }

    public static function invalidTotalAmount(float $calculated, float $provided): self
    {
        return new self(sprintf('Total amount mismatch. Calculated: %s, Provided: %s', $calculated, $provided));
    }

    public static function emptyExpandedNumbers(): self
    {
        return new self('Order must have at least one expanded number');
    }

    public static function invalidChannelWeights(): self
    {
        return new self('Channel weights must be positive integers');
    }

    public static function bettingWindowClosed(string $period): self
    {
        return new self(sprintf('Betting window is closed for %s period', $period));
    }

    public static function insufficientBalance(float $required, float $available): self
    {
        return new self(sprintf('Insufficient balance. Required: %s, Available: %s', $required, $available));
    }

    public static function invalidNumberExpansion(string $number, string $option): self
    {
        return new self(sprintf('Cannot expand number %s with option %s', $number, $option));
    }

    public static function channelNotAvailable(string $channel, string $period): self
    {
        return new self(sprintf('Channel %s is not available for %s period', $channel, $period));
    }

    public static function minimumBetAmountNotMet(float $amount, float $minimum): self
    {
        return new self(sprintf('Bet amount %s is below minimum %s', $amount, $minimum));
    }

    public static function maximumBetAmountExceeded(float $amount, float $maximum): self
    {
        return new self(sprintf('Bet amount %s exceeds maximum %s', $amount, $maximum));
    }

    public static function cartEmpty(): self
    {
        return new self('Cart is empty');
    }

    public static function cartItemNotFound(int $itemId): self
    {
        return new self(sprintf('Cart item %d not found', $itemId));
    }

    public static function duplicateCartItem(): self
    {
        return new self('Item already exists in cart');
    }

    public static function orderNotFound(string $orderNumber): self
    {
        return new self(sprintf('Order %s not found', $orderNumber));
    }

    public static function groupNotFound(string $groupId): self
    {
        return new self(sprintf('Order group %s not found', $groupId));
    }

    public static function invalidAgent(int $agentId): self
    {
        return new self('Invalid agent ID: '.$agentId);
    }

    public static function agentNotAllowedToBet(int $agentId): self
    {
        return new self(sprintf('Agent %d is not allowed to place bets', $agentId));
    }

    public static function dailyBetLimitExceeded(float $current, float $limit): self
    {
        return new self(sprintf('Daily bet limit exceeded. Current: %s, Limit: %s', $current, $limit));
    }

    public static function blockedNumber(string $number): self
    {
        return new self(sprintf('Number %s is blocked for betting', $number));
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self('Invalid currency: '.$currency);
    }

    public static function processingError(string $message): self
    {
        return new self('Order processing error: '.$message);
    }
}
