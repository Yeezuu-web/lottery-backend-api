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
        $this->validatePeriod($this->period);
        $this->validateType($this->type);
        $this->validateChannels($this->channels);
        $this->validateOption($this->option);
        $this->validateNumber($this->number);
        $this->validateAmount($this->amount);
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

    public function calculateTotalMultiplier(): int
    {
        return count($this->expandedNumbers) * array_sum($this->channelWeights);
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

    private function validatePeriod(string $period): void
    {
        $validPeriods = ['evening', 'night'];
        if (! in_array($period, $validPeriods, true)) {
            throw new ValidationException('Invalid period. Must be: '.implode(', ', $validPeriods));
        }
    }

    private function validateType(string $type): void
    {
        $validTypes = ['2D', '3D'];
        if (! in_array($type, $validTypes, true)) {
            throw new ValidationException('Invalid type. Must be: '.implode(', ', $validTypes));
        }
    }

    private function validateChannels(array $channels): void
    {
        if ($channels === []) {
            throw new ValidationException('At least one channel must be selected');
        }

        $validChannels = ['A', 'B', 'C', 'D', 'LO', 'HO', 'N', 'I'];
        foreach ($channels as $channel) {
            if (! in_array($channel, $validChannels, true)) {
                throw new ValidationException('Invalid channel: '.$channel);
            }
        }

        // Validate channel availability for period/type combination
        $this->validateChannelAvailability($channels, $this->period);
    }

    private function validateChannelAvailability(array $channels, string $period): void
    {
        $nightChannels = ['A', 'B', 'C', 'D', 'LO'];
        $eveningChannels = ['A', 'B', 'C', 'D', 'LO', 'HO', 'N', 'I'];

        $allowedChannels = $period === 'night' ? $nightChannels : $eveningChannels;

        foreach ($channels as $channel) {
            if (! in_array($channel, $allowedChannels, true)) {
                throw new ValidationException(sprintf("Channel '%s' is not available for %s period", $channel, $period));
            }
        }
    }

    private function validateOption(string $option): void
    {
        if ($option === '' || $option === '0') {
            throw new ValidationException('Option cannot be empty');
        }

        $validOptions = ['none', 'x', 'X', '\\', '>', '>|', '<|', '><', '\\|'];
        if (! in_array($option, $validOptions, true)) {
            throw new ValidationException('Invalid option: '.$option.'. Must be one of: '.implode(', ', $validOptions));
        }
    }

    private function validateNumber(string $number): void
    {
        if ($number === '' || $number === '0') {
            throw new ValidationException('Bet number cannot be empty');
        }

        if (in_array(preg_match('/^\d+$/', $number), [0, false], true)) {
            throw new ValidationException('Bet number must contain only digits');
        }

        $length = mb_strlen($number);
        if ($this->type === '2D' && $length !== 2) {
            throw new ValidationException('Bet number must be exactly 2 digits for 2D betting');
        }

        if ($this->type === '3D' && $length !== 3) {
            throw new ValidationException('Bet number must be exactly 3 digits for 3D betting');
        }
    }

    private function validateAmount(Money $amount): void
    {
        if ($amount->isZero()) {
            throw new ValidationException('Amount must be greater than zero');
        }

        if ($amount->isNegative()) {
            throw new ValidationException('Bet amount cannot be negative');
        }

        // Minimum bet amount
        $minimumBet = Money::fromAmount(100, $amount->currency());
        if ($amount->isLessThan($minimumBet)) {
            throw new ValidationException('Minimum bet amount is '.$minimumBet->amount().' '.$minimumBet->currency());
        }
    }
}
