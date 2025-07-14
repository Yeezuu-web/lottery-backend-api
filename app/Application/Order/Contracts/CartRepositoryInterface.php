<?php

declare(strict_types=1);

namespace App\Application\Order\Contracts;

use App\Domain\Agent\Models\Agent;
use App\Domain\Order\ValueObjects\BetData;

interface CartRepositoryInterface
{
    /**
     * Add an item to the cart
     *
     * @param  Agent  $agent  The agent adding the item
     * @param  BetData  $betData  The bet data
     * @param  array  $expandedNumbers  The expanded numbers
     * @param  array  $channelWeights  The channel weights
     * @return array The created cart item
     */
    public function addItem(Agent $agent, BetData $betData, array $expandedNumbers, array $channelWeights): array;

    /**
     * Get all items in the cart for an agent
     *
     * @param  Agent  $agent  The agent
     * @return array Array of cart items
     */
    public function getItems(Agent $agent): array;

    /**
     * Remove an item from the cart
     *
     * @param  Agent  $agent  The agent
     * @param  int  $itemId  The item ID to remove
     * @return bool True if removed, false otherwise
     */
    public function removeItem(Agent $agent, int $itemId): bool;

    /**
     * Clear all items from the cart
     *
     * @param  Agent  $agent  The agent
     */
    public function clearCart(Agent $agent): void;

    /**
     * Check if an existing item exists in the cart
     *
     * @param  Agent  $agent  The agent
     * @param  BetData  $betData  The bet data to check
     * @return bool True if exists, false otherwise
     */
    public function hasExistingItem(Agent $agent, BetData $betData): bool;

    /**
     * Get cart summary for an agent
     *
     * @param  Agent  $agent  The agent
     * @return array Cart summary with totals
     */
    public function getCartSummary(Agent $agent): array;

    /**
     * Update an existing cart item
     *
     * @param  Agent  $agent  The agent
     * @param  int  $itemId  The item ID
     * @param  BetData  $betData  The updated bet data
     * @param  array  $expandedNumbers  The updated expanded numbers
     * @param  array  $channelWeights  The updated channel weights
     * @return array The updated cart item
     */
    public function updateItem(Agent $agent, int $itemId, BetData $betData, array $expandedNumbers, array $channelWeights): array;

    /**
     * Get the count of items in the cart
     *
     * @param  Agent  $agent  The agent
     * @return int Number of items in cart
     */
    public function getItemCount(Agent $agent): int;

    /**
     * Get the total amount for all items in the cart
     *
     * @param  Agent  $agent  The agent
     * @return float Total amount
     */
    public function getTotalAmount(Agent $agent): float;
}
