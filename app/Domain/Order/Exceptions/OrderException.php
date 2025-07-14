<?php

namespace App\Domain\Order\Exceptions;

use App\Shared\Exceptions\DomainException;

final class OrderException extends DomainException
{
    public static function invalidBetData(string $message): self
    {
        return new self("Invalid bet data: {$message}");
    }

    public static function invalidOrderStatus(string $currentStatus, string $attemptedStatus): self
    {
        return new self("Cannot change order status from {$currentStatus} to {$attemptedStatus}");
    }

    public static function orderAlreadyPrinted(string $orderNumber): self
    {
        return new self("Order {$orderNumber} is already printed");
    }

    public static function orderCannotBeCancelled(string $orderNumber, string $status): self
    {
        return new self("Order {$orderNumber} cannot be cancelled in status: {$status}");
    }

    public static function invalidTotalAmount(float $calculated, float $provided): self
    {
        return new self("Total amount mismatch. Calculated: {$calculated}, Provided: {$provided}");
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
        return new self("Betting window is closed for {$period} period");
    }

    public static function insufficientBalance(float $required, float $available): self
    {
        return new self("Insufficient balance. Required: {$required}, Available: {$available}");
    }

    public static function invalidNumberExpansion(string $number, string $option): self
    {
        return new self("Cannot expand number {$number} with option {$option}");
    }

    public static function channelNotAvailable(string $channel, string $period): self
    {
        return new self("Channel {$channel} is not available for {$period} period");
    }

    public static function minimumBetAmountNotMet(float $amount, float $minimum): self
    {
        return new self("Bet amount {$amount} is below minimum {$minimum}");
    }

    public static function maximumBetAmountExceeded(float $amount, float $maximum): self
    {
        return new self("Bet amount {$amount} exceeds maximum {$maximum}");
    }

    public static function cartEmpty(): self
    {
        return new self('Cart is empty');
    }

    public static function cartItemNotFound(int $itemId): self
    {
        return new self("Cart item {$itemId} not found");
    }

    public static function duplicateCartItem(): self
    {
        return new self('Item already exists in cart');
    }

    public static function orderNotFound(string $orderNumber): self
    {
        return new self("Order {$orderNumber} not found");
    }

    public static function groupNotFound(string $groupId): self
    {
        return new self("Order group {$groupId} not found");
    }

    public static function invalidAgent(int $agentId): self
    {
        return new self("Invalid agent ID: {$agentId}");
    }

    public static function agentNotAllowedToBet(int $agentId): self
    {
        return new self("Agent {$agentId} is not allowed to place bets");
    }

    public static function dailyBetLimitExceeded(float $current, float $limit): self
    {
        return new self("Daily bet limit exceeded. Current: {$current}, Limit: {$limit}");
    }

    public static function blockedNumber(string $number): self
    {
        return new self("Number {$number} is blocked for betting");
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency: {$currency}");
    }

    public static function processingError(string $message): self
    {
        return new self("Order processing error: {$message}");
    }
}
