<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObjects;

use App\Domain\Wallet\ValueObjects\Money;
use App\Shared\Exceptions\ValidationException;

final readonly class BetData
{
    public function __construct(
        private string $period,
        private string $type,
        private array $channels,
        private string $option,
        private string $number,
        private Money $amount,
        private array $expandedNumbers = [],
        private array $channelWeights = [],
        private ?Money $totalAmount = null
    ) {
        $this->validatePeriod();
        $this->validateType();
        $this->validateChannels();
        $this->validateOption();
        $this->validateNumber();
        $this->validateAmount();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            period: $data['period'],
            type: $data['type'],
            channels: $data['channels'],
            option: $data['option'],
            number: $data['number'],
            amount: Money::fromAmount($data['amount'], $data['currency']),
            expandedNumbers: $data['expanded_numbers'] ?? [],
            channelWeights: $data['channel_weights'] ?? [],
            totalAmount: isset($data['total_amount']) && isset($data['currency'])
                ? Money::fromAmount($data['total_amount'], $data['currency'])
                : null
        );
    }

    public function period(): string
    {
        return $this->period;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function channels(): array
    {
        return $this->channels;
    }

    public function option(): string
    {
        return $this->option;
    }

    public function number(): string
    {
        return $this->number;
    }

    public function amount(): Money
    {
        return $this->amount;
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
        return $this->totalAmount ?? $this->amount;
    }

    public function withExpandedNumbers(array $expandedNumbers): self
    {
        return new self(
            $this->period,
            $this->type,
            $this->channels,
            $this->option,
            $this->number,
            $this->amount,
            $expandedNumbers,
            $this->channelWeights,
            $this->totalAmount
        );
    }

    public function withChannelWeights(array $channelWeights): self
    {
        return new self(
            $this->period,
            $this->type,
            $this->channels,
            $this->option,
            $this->number,
            $this->amount,
            $this->expandedNumbers,
            $channelWeights,
            $this->totalAmount
        );
    }

    public function withTotalAmount(Money $totalAmount): self
    {
        return new self(
            $this->period,
            $this->type,
            $this->channels,
            $this->option,
            $this->number,
            $this->amount,
            $this->expandedNumbers,
            $this->channelWeights,
            $totalAmount
        );
    }

    public function is2D(): bool
    {
        return $this->type === '2D';
    }

    public function is3D(): bool
    {
        return $this->type === '3D';
    }

    public function isEveningPeriod(): bool
    {
        return $this->period === 'evening';
    }

    public function isNightPeriod(): bool
    {
        return $this->period === 'night';
    }

    public function hasMultipleChannels(): bool
    {
        return count($this->channels) > 1;
    }

    public function requiresExpansion(): bool
    {
        return $this->option !== 'none';
    }

    public function calculateExpansionCount(): int
    {
        return count($this->expandedNumbers);
    }

    public function calculateChannelWeight(): int
    {
        return array_sum($this->channelWeights);
    }

    public function calculateTotalMultiplier(): int
    {
        return $this->calculateExpansionCount() * $this->calculateChannelWeight();
    }

    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'type' => $this->type,
            'channels' => $this->channels,
            'option' => $this->option,
            'number' => $this->number,
            'amount' => $this->amount->amount(),
            'currency' => $this->amount->currency(),
            'expanded_numbers' => $this->expandedNumbers,
            'channel_weights' => $this->channelWeights,
            'total_amount' => $this->totalAmount()->amount(),
        ];
    }

    public function equals(self $other): bool
    {
        return $this->period === $other->period
            && $this->type === $other->type
            && $this->channels === $other->channels
            && $this->option === $other->option
            && $this->number === $other->number
            && $this->amount->equals($other->amount);
    }

    private function validatePeriod(): void
    {
        $validPeriods = ['evening', 'night'];
        if (! in_array($this->period, $validPeriods, true)) {
            throw new ValidationException('Invalid period. Must be: '.implode(', ', $validPeriods));
        }
    }

    private function validateType(): void
    {
        $validTypes = ['2D', '3D'];
        if (! in_array($this->type, $validTypes, true)) {
            throw new ValidationException('Invalid type. Must be: '.implode(', ', $validTypes));
        }
    }

    private function validateChannels(): void
    {
        if ($this->channels === []) {
            throw new ValidationException('At least one channel must be selected');
        }

        $validChannels = ['A', 'B', 'C', 'D', 'LO', 'HO', 'N', 'I'];
        foreach ($this->channels as $channel) {
            if (! in_array($channel, $validChannels, true)) {
                throw new ValidationException('Invalid channel: '.$channel);
            }
        }

        // Validate channel availability for period/type combination
        $this->validateChannelAvailability();
    }

    private function validateChannelAvailability(): void
    {
        $nightChannels = ['A', 'B', 'C', 'D', 'LO'];
        $eveningChannels = ['A', 'B', 'C', 'D', 'LO', 'HO', 'N', 'I'];

        $allowedChannels = $this->isNightPeriod() ? $nightChannels : $eveningChannels;

        foreach ($this->channels as $channel) {
            if (! in_array($channel, $allowedChannels, true)) {
                throw new ValidationException(
                    sprintf('Channel %s is not available for %s period', $channel, $this->period)
                );
            }
        }
    }

    private function validateOption(): void
    {
        $validOptions = ['x', '\\', '>|', '\\|', '>', 'none'];
        if (! in_array($this->option, $validOptions, true)) {
            throw new ValidationException('Invalid option. Must be: '.implode(', ', $validOptions));
        }
    }

    private function validateNumber(): void
    {
        if ($this->number === '' || $this->number === '0') {
            throw new ValidationException('Number cannot be empty');
        }

        if (in_array(preg_match('/^\d+$/', $this->number), [0, false], true)) {
            throw new ValidationException('Number must contain only digits');
        }

        $length = mb_strlen($this->number);
        $expectedLength = $this->is2D() ? 2 : 3;

        if ($length !== $expectedLength) {
            throw new ValidationException(
                sprintf('Number must be %s digits for %s betting', $expectedLength, $this->type)
            );
        }
    }

    private function validateAmount(): void
    {
        if ($this->amount->isLessThanOrEqual(Money::fromAmount(0, $this->amount->currency()))) {
            throw new ValidationException('Amount must be greater than zero');
        }

        // Minimum bet amount validation
        $minimumAmount = Money::fromAmount(100, $this->amount->currency()); // 100 KHR minimum
        if ($this->amount->isLessThan($minimumAmount)) {
            throw new ValidationException(
                'Minimum bet amount is '.$minimumAmount->amount().' '.$minimumAmount->currency()
            );
        }
    }
}
