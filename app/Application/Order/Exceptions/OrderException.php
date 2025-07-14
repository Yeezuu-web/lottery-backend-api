<?php

namespace App\Application\Order\Exceptions;

use App\Shared\Exceptions\DomainException;

final class OrderException extends DomainException
{
    public static function invalidAgent(int $agentId): self
    {
        return new self("Agent with ID {$agentId} not found");
    }

    public static function agentNotAllowedToBet(int $agentId): self
    {
        return new self("Agent with ID {$agentId} is not allowed to place bets");
    }

    public static function insufficientBalance(float $required, float $available): self
    {
        return new self("Insufficient balance. Required: {$required}, Available: {$available}");
    }

    public static function duplicateCartItem(): self
    {
        return new self('This bet already exists in your cart');
    }

    public static function invalidOrderStatus(string $status): self
    {
        return new self("Invalid order status: {$status}");
    }

    public static function orderNotFound(int $orderId): self
    {
        return new self("Order with ID {$orderId} not found");
    }

    public static function orderAlreadyAccepted(int $orderId): self
    {
        return new self("Order with ID {$orderId} is already accepted");
    }

    public static function orderAlreadyCancelled(int $orderId): self
    {
        return new self("Order with ID {$orderId} is already cancelled");
    }

    public static function cannotCancelAcceptedOrder(int $orderId): self
    {
        return new self("Cannot cancel order with ID {$orderId} as it is already accepted");
    }
}
