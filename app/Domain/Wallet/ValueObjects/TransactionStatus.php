<?php

namespace App\Domain\Wallet\ValueObjects;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REVERSED = 'reversed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REVERSED => 'Reversed',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Transaction is pending processing',
            self::PROCESSING => 'Transaction is being processed',
            self::COMPLETED => 'Transaction completed successfully',
            self::FAILED => 'Transaction failed due to error',
            self::CANCELLED => 'Transaction was cancelled',
            self::REVERSED => 'Transaction was reversed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'gray',
            self::REVERSED => 'orange',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING]);
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isReversed(): bool
    {
        return $this === self::REVERSED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED, self::REVERSED]);
    }

    public function canTransitionTo(TransactionStatus $targetStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($targetStatus, [self::PROCESSING, self::COMPLETED, self::FAILED, self::CANCELLED]),
            self::PROCESSING => in_array($targetStatus, [self::COMPLETED, self::FAILED, self::CANCELLED]),
            self::COMPLETED => $targetStatus === self::REVERSED,
            self::FAILED => false,
            self::CANCELLED => false,
            self::REVERSED => false,
        };
    }

    public function getValidTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::COMPLETED, self::FAILED, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::FAILED, self::CANCELLED],
            self::COMPLETED => [self::REVERSED],
            self::FAILED => [],
            self::CANCELLED => [],
            self::REVERSED => [],
        };
    }

    public static function getActiveStatuses(): array
    {
        return [self::PENDING, self::PROCESSING];
    }

    public static function getFinalStatuses(): array
    {
        return [self::COMPLETED, self::FAILED, self::CANCELLED, self::REVERSED];
    }

    public static function getSuccessStatuses(): array
    {
        return [self::COMPLETED];
    }

    public static function getFailureStatuses(): array
    {
        return [self::FAILED, self::CANCELLED, self::REVERSED];
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
            'is_active' => $this->isActive(),
            'is_final' => $this->isFinal(),
            'valid_transitions' => array_map(fn ($status) => $status->value, $this->getValidTransitions()),
        ];
    }
}
