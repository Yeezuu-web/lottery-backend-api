<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Order\ValueObjects\GroupId;
use App\Domain\Order\ValueObjects\OrderNumber;
use App\Domain\Wallet\ValueObjects\Money;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;

final class Order
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $agentId,
        private readonly OrderNumber $orderNumber,
        private readonly GroupId $groupId,
        private readonly BetData $betData,
        private readonly array $expandedNumbers,
        private readonly array $channelWeights,
        private readonly Money $totalAmount,
        private readonly string $status = 'pending',
        private readonly bool $isPrinted = false,
        private readonly ?DateTimeImmutable $printedAt = null,
        private ?DateTimeImmutable $placedAt = null,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null
    ) {
        $this->validateStatus();
        $this->validateExpandedNumbers();
        $this->validateChannelWeights();
        $this->validateTotalAmount();

        $this->placedAt = $placedAt ?? new DateTimeImmutable;
        $this->createdAt = $createdAt ?? new DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable;
    }

    // Factory methods for creating orders
    public static function create(
        int $agentId,
        OrderNumber $orderNumber,
        GroupId $groupId,
        BetData $betData,
        array $expandedNumbers,
        array $channelWeights,
        Money $totalAmount
    ): self {
        return new self(
            null,
            $agentId,
            $orderNumber,
            $groupId,
            $betData,
            $expandedNumbers,
            $channelWeights,
            $totalAmount,
            'pending'
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function orderNumber(): OrderNumber
    {
        return $this->orderNumber;
    }

    public function groupId(): GroupId
    {
        return $this->groupId;
    }

    public function betData(): BetData
    {
        return $this->betData;
    }

    public function expandedNumbers(): array
    {
        return $this->expandedNumbers;
    }

    public function channelWeights(): array
    {
        return $this->channelWeights;
    }

    public function totalAmount(): Money
    {
        return $this->totalAmount;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isPrinted(): bool
    {
        return $this->isPrinted;
    }

    public function printedAt(): ?DateTimeImmutable
    {
        return $this->printedAt;
    }

    public function placedAt(): DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Status management methods
    public function accept(): self
    {
        if ($this->status === 'accepted') {
            throw new ValidationException('Order is already accepted');
        }

        if ($this->status === 'cancelled') {
            throw new ValidationException('Cannot accept a cancelled order');
        }

        return $this->withStatus('accepted');
    }

    public function cancel(): self
    {
        if ($this->status === 'accepted') {
            throw new ValidationException('Cannot cancel an accepted order');
        }

        if ($this->status === 'cancelled') {
            throw new ValidationException('Order is already cancelled');
        }

        return $this->withStatus('cancelled');
    }

    public function markAsWon(): self
    {
        if ($this->status !== 'accepted') {
            throw new ValidationException('Only accepted orders can be marked as won');
        }

        return $this->withStatus('won');
    }

    public function markAsLost(): self
    {
        if ($this->status !== 'accepted') {
            throw new ValidationException('Only accepted orders can be marked as lost');
        }

        return $this->withStatus('lost');
    }

    // Printing methods
    public function markAsPrinted(): self
    {
        if ($this->isPrinted) {
            throw new ValidationException('Order is already printed');
        }

        return new self(
            $this->id,
            $this->agentId,
            $this->orderNumber,
            $this->groupId,
            $this->betData,
            $this->expandedNumbers,
            $this->channelWeights,
            $this->totalAmount,
            $this->status,
            true,
            new DateTimeImmutable,
            $this->placedAt,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    // Business logic methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    public function canBePrinted(): bool
    {
        return $this->isAccepted() && ! $this->isPrinted;
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }

    public function calculateExpansionCount(): int
    {
        return count($this->expandedNumbers);
    }

    public function calculateTotalChannelWeight(): int
    {
        return array_sum($this->channelWeights);
    }

    public function calculateTotalMultiplier(): int
    {
        return $this->calculateExpansionCount() * $this->calculateTotalChannelWeight();
    }

    public function calculatePotentialWinAmount(): Money
    {
        // This would depend on the payout multiplier configuration
        // For now, returning a placeholder implementation
        $payoutMultiplier = 90; // Example: 90x payout for 2D

        return $this->betData->amount()->multiply($payoutMultiplier);
    }

    public function isNumberWinning(string $drawnNumber): bool
    {
        return in_array($drawnNumber, $this->expandedNumbers, true);
    }

    public function countWinningNumbers(array $drawnNumbers): int
    {
        return count(array_intersect($this->expandedNumbers, $drawnNumbers));
    }

    // Helper methods for creating modified instances
    private function withStatus(string $status): self
    {
        return new self(
            $this->id,
            $this->agentId,
            $this->orderNumber,
            $this->groupId,
            $this->betData,
            $this->expandedNumbers,
            $this->channelWeights,
            $this->totalAmount,
            $status,
            $this->isPrinted,
            $this->printedAt,
            $this->placedAt,
            $this->createdAt,
            new DateTimeImmutable
        );
    }

    // Validation methods
    private function validateStatus(): void
    {
        $validStatuses = ['pending', 'accepted', 'cancelled', 'won', 'lost'];
        if (! in_array($this->status, $validStatuses, true)) {
            throw new ValidationException('Invalid order status: '.$this->status);
        }
    }

    private function validateExpandedNumbers(): void
    {
        if ($this->expandedNumbers === []) {
            throw new ValidationException('Order must have at least one expanded number');
        }

        foreach ($this->expandedNumbers as $number) {
            if (! is_string($number) || in_array(preg_match('/^\d+$/', $number), [0, false], true)) {
                throw new ValidationException('All expanded numbers must be valid digit strings');
            }
        }
    }

    private function validateChannelWeights(): void
    {
        if ($this->channelWeights === []) {
            throw new ValidationException('Order must have channel weights');
        }

        foreach ($this->channelWeights as $channel => $weight) {
            if (! is_string($channel) || ! is_int($weight) || $weight <= 0) {
                throw new ValidationException('Channel weights must be positive integers');
            }
        }
    }

    private function validateTotalAmount(): void
    {
        if ($this->totalAmount->isLessThanOrEqual(Money::fromAmount(0, $this->totalAmount->currency()))) {
            throw new ValidationException('Total amount must be greater than zero');
        }

        // Validate that total amount matches calculation
        $expectedTotal = $this->betData->amount()->multiply($this->calculateTotalMultiplier());
        if (! $this->totalAmount->equals($expectedTotal)) {
            throw new ValidationException(
                'Total amount does not match calculated amount. '.
                'Expected: '.$expectedTotal->amount().', '.
                'Got: '.$this->totalAmount->amount()
            );
        }
    }
}
