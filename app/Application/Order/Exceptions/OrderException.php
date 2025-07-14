<?php

declare(strict_types=1);

namespace App\Application\Order\Exceptions;

use App\Shared\Exceptions\DomainException;

final class OrderException extends DomainException
{
    public static function invalidAgent(int $agentId): self
    {
        return new self(sprintf('Agent with ID %d not found', $agentId));
    }

    public static function agentNotAllowedToBet(int $agentId): self
    {
        return new self(sprintf('Agent with ID %d is not allowed to place bets', $agentId));
    }

    public static function insufficientBalance(float $required, float $available): self
    {
        return new self(sprintf('Insufficient balance. Required: %s, Available: %s', $required, $available));
    }

    public static function duplicateCartItem(): self
    {
        return new self('This bet already exists in your cart');
    }

    public static function invalidOrderStatus(string $status): self
    {
        return new self('Invalid order status: '.$status);
    }

    public static function orderNotFound(int $orderId): self
    {
        return new self(sprintf('Order with ID %d not found', $orderId));
    }

    public static function orderAlreadyAccepted(int $orderId): self
    {
        return new self(sprintf('Order with ID %d is already accepted', $orderId));
    }

    public static function orderAlreadyCancelled(int $orderId): self
    {
        return new self(sprintf('Order with ID %d is already cancelled', $orderId));
    }

    public static function cannotCancelAcceptedOrder(int $orderId): self
    {
        return new self(sprintf('Cannot cancel order with ID %d as it is already accepted', $orderId));
    }
}
