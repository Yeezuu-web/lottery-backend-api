<?php

declare(strict_types=1);

namespace App\Application\Order\UseCases;

use App\Application\Order\Commands\AddToCartCommand;
use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\Contracts\ChannelWeightServiceInterface;
use App\Application\Order\Contracts\NumberExpansionServiceInterface;
use App\Application\Order\Contracts\WalletServiceInterface;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Order\Events\ItemAddedToCart;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Wallet\ValueObjects\Money;

final readonly class AddToCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private AgentRepositoryInterface $agentRepository,
        private NumberExpansionServiceInterface $numberExpansionService,
        private ChannelWeightServiceInterface $channelWeightService,
        private WalletServiceInterface $walletService
    ) {}

    public function execute(AddToCartCommand $command): array
    {
        // 1. Validate and get agent
        $agent = $this->agentRepository->findById($command->agentId());
        if (! $agent instanceof Agent) {
            throw OrderException::invalidAgent($command->agentId());
        }

        // 2. Validate betting permissions
        if (! $agent->canPlaceBets()) {
            throw OrderException::agentNotAllowedToBet($command->agentId());
        }

        // 3. Create bet data with validation
        $betData = $this->createBetData($command);

        // 4. Expand numbers based on option
        $expandedNumbers = $this->numberExpansionService->expandNumbers(
            $betData->number(),
            $betData->option()
        );

        // 5. Calculate channel weights
        $channelWeights = $this->channelWeightService->calculateWeights(
            $betData->channels(),
            $betData->period(),
            $betData->type()
        );

        // 6. Update bet data with expanded numbers and weights
        $betData = $betData
            ->withExpandedNumbers($expandedNumbers)
            ->withChannelWeights($channelWeights);

        // 7. Calculate total amount
        $totalAmount = $this->calculateTotalAmount($betData);
        $betData = $betData->withTotalAmount($totalAmount);

        // 8. Validate wallet balance
        if (! $this->walletService->hasEnoughBalance($agent, $totalAmount)) {
            $balance = $this->walletService->getBalance($agent);
            throw OrderException::insufficientBalance($totalAmount->amount(), $balance->amount());
        }

        // 9. Check for duplicate items in cart
        if ($this->cartRepository->hasExistingItem($agent, $betData)) {
            throw OrderException::duplicateCartItem();
        }

        // 10. Add item to cart
        $cartItem = $this->cartRepository->addItem($agent, $betData, $expandedNumbers, $channelWeights);

        // 11. Emit domain event
        $event = ItemAddedToCart::now($agent->id(), $betData, $expandedNumbers, $channelWeights);

        // 12. Get updated cart summary
        $cartSummary = $this->cartRepository->getCartSummary($agent);

        return [
            'cart_item' => $cartItem,
            'cart_summary' => $cartSummary,
            'bet_details' => [
                'original_number' => $betData->number(),
                'expanded_numbers' => $expandedNumbers,
                'expansion_count' => count($expandedNumbers),
                'channels' => $betData->channels(),
                'channel_weights' => $channelWeights,
                'total_weight' => array_sum($channelWeights),
                'base_amount' => $betData->amount()->amount(),
                'total_amount' => $totalAmount->amount(),
                'multiplier' => $betData->calculateTotalMultiplier(),
                'currency' => $betData->amount()->currency(),
            ],
            'event' => $event,
        ];
    }

    private function createBetData(AddToCartCommand $command): BetData
    {
        $amount = Money::fromAmount($command->amount(), $command->currency());

        return new BetData(
            $command->period(),
            $command->type(),
            $command->channels(),
            $command->option(),
            $command->number(),
            $amount
        );
    }

    private function calculateTotalAmount(BetData $betData): Money
    {
        $expansionCount = count($betData->expandedNumbers());
        $totalWeight = array_sum($betData->channelWeights());
        $multiplier = $expansionCount * $totalWeight;

        return $betData->amount()->multiply($multiplier);
    }
}
