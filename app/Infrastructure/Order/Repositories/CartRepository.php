<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Repositories;

use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Wallet\ValueObjects\Money;
use App\Infrastructure\Order\Models\EloquentCart;
use DateTimeImmutable;
use Exception;

final readonly class CartRepository implements CartRepositoryInterface
{
    public function __construct(
        private EloquentCart $model
    ) {}

    public function addItem(Agent $agent, BetData $betData, array $expandedNumbers, array $channelWeights): array
    {
        $totalAmount = $this->calculateTotalAmount($betData, $expandedNumbers, $channelWeights);

        $cartItem = new EloquentCart;
        $cartItem->agent_id = $agent->id();
        $cartItem->bet_data = $betData->toArray();
        $cartItem->expanded_numbers = $expandedNumbers;
        $cartItem->channel_weights = $channelWeights;
        $cartItem->total_amount = $totalAmount->amount();
        $cartItem->currency = $totalAmount->currency();
        $cartItem->status = 'active';

        $cartItem->save();

        return [
            'id' => $cartItem->id,
            'agent_id' => $cartItem->agent_id,
            'bet_data' => $betData,
            'expanded_numbers' => $expandedNumbers,
            'channel_weights' => $channelWeights,
            'total_amount' => $totalAmount->amount(),
            'currency' => $totalAmount->currency(),
            'status' => $cartItem->status,
            'created_at' => DateTimeImmutable::createFromMutable($cartItem->created_at),
            'updated_at' => DateTimeImmutable::createFromMutable($cartItem->updated_at),
        ];
    }

    public function getItems(Agent $agent): array
    {
        $cartItems = $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return $cartItems->map(fn ($item): array => [
            'id' => $item->id,
            'agent_id' => $item->agent_id,
            'bet_data' => BetData::fromArray($item->bet_data),
            'expanded_numbers' => $item->expanded_numbers,
            'channel_weights' => $item->channel_weights,
            'total_amount' => $item->total_amount,
            'currency' => $item->currency,
            'status' => $item->status,
            'created_at' => DateTimeImmutable::createFromMutable($item->created_at),
            'updated_at' => DateTimeImmutable::createFromMutable($item->updated_at),
        ])->toArray();
    }

    public function removeItem(Agent $agent, int $itemId): bool
    {
        $deleted = $this->model
            ->where('id', $itemId)
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->delete();

        return $deleted > 0;
    }

    public function clearCart(Agent $agent): void
    {
        $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->delete();
    }

    public function hasExistingItem(Agent $agent, BetData $betData): bool
    {
        $betDataArray = $betData->toArray();

        // Get all active cart items for this agent
        $cartItems = $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->get();

        // Check each cart item for a match
        foreach ($cartItems as $cartItem) {
            $existingBetData = $cartItem->bet_data;

            // Compare all the key fields that determine uniqueness
            if (
                $existingBetData['period'] === $betDataArray['period'] &&
                $existingBetData['type'] === $betDataArray['type'] &&
                $existingBetData['option'] === $betDataArray['option'] &&
                $existingBetData['number'] === $betDataArray['number'] &&
                $existingBetData['amount'] === $betDataArray['amount'] &&
                $existingBetData['channels'] === $betDataArray['channels']
            ) {
                return true;
            }
        }

        return false;
    }

    public function getCartSummary(Agent $agent): array
    {
        $items = $this->getItems($agent);
        $totalAmount = 0;
        $currency = 'KHR';

        foreach ($items as $item) {
            $totalAmount += $item['total_amount'];
            $currency = $item['currency'];
        }

        return [
            'total_items' => count($items),
            'total_amount' => $totalAmount,
            'currency' => $currency,
        ];
    }

    public function updateItem(Agent $agent, int $itemId, BetData $betData, array $expandedNumbers, array $channelWeights): array
    {
        $totalAmount = $this->calculateTotalAmount($betData, $expandedNumbers, $channelWeights);

        $cartItem = $this->model
            ->where('id', $itemId)
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->first();

        if (! $cartItem) {
            throw new Exception('Cart item not found');
        }

        $cartItem->bet_data = $betData->toArray();
        $cartItem->expanded_numbers = $expandedNumbers;
        $cartItem->channel_weights = $channelWeights;
        $cartItem->total_amount = $totalAmount->amount();
        $cartItem->currency = $totalAmount->currency();
        $cartItem->updated_at = now();

        $cartItem->save();

        return [
            'id' => $cartItem->id,
            'agent_id' => $cartItem->agent_id,
            'bet_data' => $betData,
            'expanded_numbers' => $expandedNumbers,
            'channel_weights' => $channelWeights,
            'total_amount' => $totalAmount->amount(),
            'currency' => $totalAmount->currency(),
            'status' => $cartItem->status,
            'created_at' => DateTimeImmutable::createFromMutable($cartItem->created_at),
            'updated_at' => DateTimeImmutable::createFromMutable($cartItem->updated_at),
        ];
    }

    public function getItemCount(Agent $agent): int
    {
        return $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->count();
    }

    public function getTotalAmount(Agent $agent): float
    {
        return $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->sum('total_amount');
    }

    public function markCartAsSubmitted(Agent $agent): void
    {
        $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->update([
                'status' => 'submitted',
                'updated_at' => now(),
            ]);
    }

    private function calculateTotalAmount(BetData $betData, array $expandedNumbers, array $channelWeights): Money
    {
        $baseAmount = $betData->amount();
        $expansionCount = count($expandedNumbers);
        $totalWeight = array_sum($channelWeights);
        $multiplier = $expansionCount * $totalWeight;

        return $baseAmount->multiply($multiplier);
    }
}
