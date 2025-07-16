<?php

declare(strict_types=1);

namespace App\Application\Order\UseCases;

use App\Application\Order\Commands\SubmitCartCommand;
use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\Contracts\OrderRepositoryInterface;
use App\Application\Order\Contracts\WalletServiceInterface;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\AgentSettings\Contracts\BettingLimitValidationServiceInterface;
use App\Domain\Order\Events\CartSubmitted;
use App\Domain\Order\Events\OrderPlaced;
use App\Domain\Order\Exceptions\CartException;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\ValueObjects\GroupId;
use App\Domain\Order\ValueObjects\OrderNumber;

final readonly class SubmitCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private OrderRepositoryInterface $orderRepository,
        private AgentRepositoryInterface $agentRepository,
        private WalletServiceInterface $walletService,
        private BettingLimitValidationServiceInterface $bettingLimitValidationService
    ) {}

    public function execute(SubmitCartCommand $command): array
    {
        // 1. Validate and get agent
        $agent = $this->agentRepository->findById($command->agentId());
        if (! $agent instanceof Agent) {
            throw OrderException::invalidAgent($command->agentId());
        }

        // 2. Get cart items
        $cartItems = $this->cartRepository->getItems($agent);
        if ($cartItems === []) {
            throw CartException::empty();
        }

        // 3. Validate betting permissions
        if (! $agent->canPlaceBets()) {
            throw OrderException::agentNotAllowedToBet($command->agentId());
        }

        // 4. Calculate total amount
        $totalAmount = $this->calculateTotalAmount($cartItems);

        // 5. Re-validate all betting limits for cart submission
        $this->validateCartLimits($command->agentId(), $cartItems);

        // 6. Validate wallet balance
        if (! $this->walletService->hasEnoughBalance($agent, $totalAmount)) {
            $balance = $this->walletService->getBalance($agent);
            throw OrderException::insufficientBalance($totalAmount->amount(), $balance->amount());
        }

        // 7. Generate group ID for this submission
        $groupId = GroupId::generate();

        // 8. Create orders from cart items
        $orders = $this->createOrdersFromCart($agent, $cartItems, $groupId);

        // 9. Begin transaction
        return $this->orderRepository->transaction(function () use ($agent, $orders, $totalAmount, $groupId, $cartItems): array {
            // 10. Deduct wallet balance
            $this->walletService->deductBalance($agent, $totalAmount, 'Cart submission - Group: '.$groupId->value());

            // 11. Save orders
            $orderIds = [];
            foreach ($orders as $order) {
                $savedOrder = $this->orderRepository->save($order);
                $orderIds[] = $savedOrder->id();

                // Emit order placed event
                $orderPlacedEvent = OrderPlaced::now($savedOrder);
            }

            // 12. Accept orders (change status to accepted)
            $acceptedOrders = [];
            foreach ($orders as $order) {
                $acceptedOrder = $order->accept();
                $this->orderRepository->save($acceptedOrder);
                $acceptedOrders[] = $acceptedOrder;
            }

            // 13. Record usage in agent settings cache after successful submission
            $this->recordBettingUsage($agent->id(), $cartItems);

            // 14. Clear cart
            $this->cartRepository->clearCart($agent);

            // 15. Emit cart submitted event
            $cartSubmittedEvent = CartSubmitted::now($agent->id(), $groupId, $orderIds, $totalAmount);

            // 16. Return result
            return [
                'success' => true,
                'group_id' => $groupId->value(),
                'orders' => $acceptedOrders,
                'order_count' => count($acceptedOrders),
                'total_amount' => $totalAmount->amount(),
                'currency' => $totalAmount->currency(),
                'new_balance' => $this->walletService->getBalance($agent)->amount(),
                'events' => [
                    'cart_submitted' => $cartSubmittedEvent,
                    'orders_placed' => array_map(fn ($order): OrderPlaced => OrderPlaced::now($order), $acceptedOrders),
                ],
            ];
        });
    }

    private function validateCartLimits(int $agentId, array $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $betData = $cartItem['bet_data'];
            $expandedNumbers = $cartItem['expanded_numbers'] ?? [];
            $totalAmount = $cartItem['total_amount'] ?? 0;

            // Extract game type from bet data
            $gameType = $betData->type() ?? '';

            // Validate this cart item against agent settings
            $this->bettingLimitValidationService->validateBet(
                $agentId,
                $gameType,
                $expandedNumbers,
                (int) $totalAmount
            );
        }
    }

    private function recordBettingUsage(int $agentId, array $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $betData = $cartItem['bet_data'];
            $expandedNumbers = $cartItem['expanded_numbers'] ?? [];
            $totalAmount = $cartItem['total_amount'] ?? 0;

            // Extract game type from bet data
            $gameType = $betData->type() ?? '';

            // Record usage for this cart item
            $this->bettingLimitValidationService->recordUsage(
                $agentId,
                $gameType,
                $expandedNumbers,
                (int) $totalAmount
            );
        }
    }

    private function calculateTotalAmount(array $cartItems): \App\Domain\Wallet\ValueObjects\Money
    {
        $totalAmount = 0;
        $currency = 'KHR';

        foreach ($cartItems as $item) {
            $totalAmount += $item['total_amount'];
            $currency = $item['currency'] ?? $currency;
        }

        return \App\Domain\Wallet\ValueObjects\Money::fromAmount($totalAmount, $currency);
    }

    private function createOrdersFromCart(Agent $agent, array $cartItems, GroupId $groupId): array
    {
        $orders = [];

        foreach ($cartItems as $cartItem) {
            $orderNumber = OrderNumber::generate();
            $betData = $cartItem['bet_data'];
            $expandedNumbers = $cartItem['expanded_numbers'];
            $channelWeights = $cartItem['channel_weights'];
            $totalAmount = \App\Domain\Wallet\ValueObjects\Money::fromAmount($cartItem['total_amount'], $cartItem['currency'] ?? 'KHR');

            $order = Order::create(
                $agent->id(),
                $orderNumber,
                $groupId,
                $betData,
                $expandedNumbers,
                $channelWeights,
                $totalAmount
            );

            $orders[] = $order;
        }

        return $orders;
    }
}
