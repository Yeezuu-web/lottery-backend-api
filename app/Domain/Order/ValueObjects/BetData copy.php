<?php

namespace App\Domain\Order\ValueObjects;

use App\Domain\Wallet\ValueObjects\Money;
use App\Shared\Exceptions\ValidationException;

final class BetData
{
    private readonly string $number;

    private readonly array $channels;

    private readonly array $provinces;

    private readonly string $type;

    private readonly string $period;

    private readonly string $option;

    private readonly Money $amount;

    public function __construct(
        string $number,
        array $channels,
        array $provinces,
        string $type,
        string $period,
        string $option,
        Money $amount
    ) {
        $this->validateNumber($number);
        $this->validateChannels($channels);
        $this->validateProvinces($provinces);
        $this->validateType($type);
        $this->validatePeriod($period);
        $this->validateOption($option);
        $this->validateAmount($amount);

        $this->number = $number;
        $this->channels = $channels;
        $this->provinces = $provinces;
        $this->type = $type;
        $this->period = $period;
        $this->option = $option;
        $this->amount = $amount;
    }

    public function number(): string
    {
        return $this->number;
    }

    public function channels(): array
    {
        return $this->channels;
    }

    public function provinces(): array
    {
        return $this->provinces;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function period(): string
    {
        return $this->period;
    }

    public function option(): string
    {
        return $this->option;
    }

    public function amount(): Money
    {
        return $this->amount;
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

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'channels' => $this->channels,
            'provinces' => $this->provinces,
            'type' => $this->type,
            'period' => $this->period,
            'option' => $this->option,
            'amount' => $this->amount->amount(),
            'currency' => $this->amount->currency(),
        ];
    }

    private function validateNumber(string $number): void
    {
        if (empty($number)) {
            throw new ValidationException('Bet number cannot be empty');
        }

        if (! preg_match('/^\d+$/', $number)) {
            throw new ValidationException('Bet number must contain only digits');
        }

        $length = strlen($number);
        if ($length < 2 || $length > 4) {
            throw new ValidationException('Bet number must be between 2 and 4 digits');
        }
    }

    private function validateChannels(array $channels): void
    {
        if (empty($channels)) {
            throw new ValidationException('At least one channel must be selected');
        }

        $validChannels = ['A', 'B', 'C', 'D'];
        foreach ($channels as $channel) {
            if (! in_array($channel, $validChannels, true)) {
                throw new ValidationException('Invalid channel: '.$channel);
            }
        }
    }

    private function validateProvinces(array $provinces): void
    {
        if (empty($provinces)) {
            throw new ValidationException('At least one province must be selected');
        }

        $validProvinces = ['PP', 'SR', 'KP', 'BB'];
        foreach ($provinces as $province) {
            if (! in_array($province, $validProvinces, true)) {
                throw new ValidationException('Invalid province: '.$province);
            }
        }
    }

    private function validateType(string $type): void
    {
        if (! in_array($type, ['2D', '3D'], true)) {
            throw new ValidationException('Type must be either 2D or 3D');
        }
    }

    private function validatePeriod(string $period): void
    {
        if (! in_array($period, ['evening', 'night'], true)) {
            throw new ValidationException('Period must be either evening or night');
        }
    }

    private function validateOption(string $option): void
    {
        if (empty($option)) {
            throw new ValidationException('Option cannot be empty');
        }

        $validOptions = ['x', '\\', '>|', '<|', '><'];
        if (! in_array($option, $validOptions, true)) {
            throw new ValidationException('Invalid option: '.$option);
        }
    }

    private function validateAmount(Money $amount): void
    {
        if ($amount->isZero()) {
            throw new ValidationException('Bet amount cannot be zero');
        }

        if ($amount->isNegative()) {
            throw new ValidationException('Bet amount cannot be negative');
        }

        // Minimum bet amount
        $minimumBet = new Money(100, $amount->currency());
        if ($amount->isLessThan($minimumBet)) {
            throw new ValidationException('Minimum bet amount is '.$minimumBet);
        }
    }
}
