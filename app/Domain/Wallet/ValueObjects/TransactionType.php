<?php

namespace App\Domain\Wallet\ValueObjects;

enum TransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case BET_PLACED = 'bet_placed';
    case BET_WON = 'bet_won';
    case BET_REFUND = 'bet_refund';
    case COMMISSION_EARNED = 'commission_earned';
    case COMMISSION_SHARED = 'commission_shared';
    case BONUS_ADDED = 'bonus_added';
    case BONUS_USED = 'bonus_used';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case ADJUSTMENT = 'adjustment';
    case FEE = 'fee';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREDIT => 'Credit',
            self::DEBIT => 'Debit',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
            self::BET_PLACED => 'Bet Placed',
            self::BET_WON => 'Bet Won',
            self::BET_REFUND => 'Bet Refund',
            self::COMMISSION_EARNED => 'Commission Earned',
            self::COMMISSION_SHARED => 'Commission Shared',
            self::BONUS_ADDED => 'Bonus Added',
            self::BONUS_USED => 'Bonus Used',
            self::DEPOSIT => 'Deposit',
            self::WITHDRAWAL => 'Withdrawal',
            self::ADJUSTMENT => 'Adjustment',
            self::FEE => 'Fee',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::CREDIT => 'General credit transaction',
            self::DEBIT => 'General debit transaction',
            self::TRANSFER_IN => 'Money transferred into wallet',
            self::TRANSFER_OUT => 'Money transferred out of wallet',
            self::BET_PLACED => 'Bet placed by user',
            self::BET_WON => 'Winning from bet',
            self::BET_REFUND => 'Refund from cancelled bet',
            self::COMMISSION_EARNED => 'Commission earned from downline',
            self::COMMISSION_SHARED => 'Commission shared with upline',
            self::BONUS_ADDED => 'Bonus credits added',
            self::BONUS_USED => 'Bonus credits used',
            self::DEPOSIT => 'Deposit from external source',
            self::WITHDRAWAL => 'Withdrawal to external destination',
            self::ADJUSTMENT => 'Manual adjustment',
            self::FEE => 'Fee charged',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [
            self::CREDIT,
            self::TRANSFER_IN,
            self::BET_WON,
            self::BET_REFUND,
            self::COMMISSION_EARNED,
            self::BONUS_ADDED,
            self::DEPOSIT,
        ]);
    }

    public function isDebit(): bool
    {
        return in_array($this, [
            self::DEBIT,
            self::TRANSFER_OUT,
            self::BET_PLACED,
            self::COMMISSION_SHARED,
            self::BONUS_USED,
            self::WITHDRAWAL,
            self::FEE,
        ]);
    }

    public function requiresReference(): bool
    {
        return in_array($this, [
            self::BET_PLACED,
            self::BET_WON,
            self::BET_REFUND,
            self::COMMISSION_EARNED,
            self::COMMISSION_SHARED,
            self::TRANSFER_IN,
            self::TRANSFER_OUT,
        ]);
    }

    public function isReversible(): bool
    {
        return in_array($this, [
            self::CREDIT,
            self::DEBIT,
            self::BONUS_ADDED,
            self::ADJUSTMENT,
        ]);
    }

    public function getCategory(): string
    {
        return match ($this) {
            self::CREDIT, self::DEBIT => 'general',
            self::TRANSFER_IN, self::TRANSFER_OUT => 'transfer',
            self::BET_PLACED, self::BET_WON, self::BET_REFUND => 'betting',
            self::COMMISSION_EARNED, self::COMMISSION_SHARED => 'commission',
            self::BONUS_ADDED, self::BONUS_USED => 'bonus',
            self::DEPOSIT, self::WITHDRAWAL => 'external',
            self::ADJUSTMENT, self::FEE => 'administrative',
        };
    }

    public static function getCreditTypes(): array
    {
        return array_filter(self::cases(), fn ($type) => $type->isCredit());
    }

    public static function getDebitTypes(): array
    {
        return array_filter(self::cases(), fn ($type) => $type->isDebit());
    }

    public static function getByCategory(string $category): array
    {
        return array_filter(self::cases(), fn ($type) => $type->getCategory() === $category);
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'is_credit' => $this->isCredit(),
            'is_debit' => $this->isDebit(),
            'category' => $this->getCategory(),
            'requires_reference' => $this->requiresReference(),
            'is_reversible' => $this->isReversible(),
        ];
    }
}
