<?php

declare(strict_types=1);

namespace App\Domain\Wallet\ValueObjects;

enum WalletType: string
{
    case MAIN = 'main';
    case COMMISSION = 'commission';
    case BONUS = 'bonus';
    case PENDING = 'pending';
    case LOCKED = 'locked';

    public static function getAll(): array
    {
        return [
            self::MAIN,
            self::COMMISSION,
            self::BONUS,
            self::PENDING,
            self::LOCKED,
        ];
    }

    public static function getActive(): array
    {
        return [
            self::MAIN,
            self::COMMISSION,
            self::BONUS,
        ];
    }

    public static function getTransferable(): array
    {
        return [
            self::MAIN,
            self::COMMISSION,
            self::BONUS,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MAIN => 'Main Wallet',
            self::COMMISSION => 'Commission Wallet',
            self::BONUS => 'Bonus Wallet',
            self::PENDING => 'Pending Wallet',
            self::LOCKED => 'Locked Wallet',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::MAIN => 'Primary wallet for betting and transactions',
            self::COMMISSION => 'Wallet for commission earnings',
            self::BONUS => 'Wallet for bonus and promotional credits',
            self::PENDING => 'Wallet for pending transactions',
            self::LOCKED => 'Wallet for locked funds',
        };
    }

    public function canTransferTo(WalletType $targetType): bool
    {
        return match ($this) {
            self::MAIN => in_array($targetType, [self::MAIN, self::COMMISSION, self::BONUS]),
            self::COMMISSION => in_array($targetType, [self::MAIN, self::COMMISSION]),
            self::BONUS => $targetType === self::MAIN,
            self::PENDING => false, // Pending wallets can't transfer
            self::LOCKED => false, // Locked wallets can't transfer
        };
    }

    public function canReceiveFrom(WalletType $sourceType): bool
    {
        return $sourceType->canTransferTo($this);
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::PENDING, self::LOCKED]);
    }

    public function isTransferable(): bool
    {
        return $this->isActive();
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'is_active' => $this->isActive(),
            'is_transferable' => $this->isTransferable(),
        ];
    }
}
