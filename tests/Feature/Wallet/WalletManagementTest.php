<?php

declare(strict_types=1);
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionType;
use App\Domain\Wallet\ValueObjects\WalletType;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

uses(Illuminate\Foundation\Testing\WithFaker::class);

beforeEach(function (): void {
    // Set up test data if needed
});
test('can get wallet types', function (): void {
    $response = $this->getJson('/api/v1/wallet/wallet-types');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'value',
                    'label',
                    'description',
                    'is_active',
                    'is_transferable',
                ],
            ],
            'message',
        ]);
});
test('can get transaction types', function (): void {
    $response = $this->getJson('/api/v1/wallet/transaction-types');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'value',
                    'label',
                    'description',
                    'is_credit',
                    'is_debit',
                    'category',
                ],
            ],
            'message',
        ]);
});
test('can get credit transaction types', function (): void {
    $response = $this->getJson('/api/v1/wallet/transaction-types/credit');

    $response->assertStatus(200);

    $creditTypes = $response->json('data');
    foreach ($creditTypes as $type) {
        expect($type['is_credit'])->toBeTrue();
        expect($type['is_debit'])->toBeFalse();
    }
});
test('can get debit transaction types', function (): void {
    $response = $this->getJson('/api/v1/wallet/transaction-types/debit');

    $response->assertStatus(200);

    $debitTypes = $response->json('data');
    foreach ($debitTypes as $type) {
        expect($type['is_credit'])->toBeFalse();
        expect($type['is_debit'])->toBeTrue();
    }
});
test('wallet creation requires authentication', function (): void {
    $response = $this->postJson('/api/v1/wallet/wallets', [
        'owner_id' => 1,
        'wallet_type' => 'main',
        'currency' => 'KHR',
    ]);

    // This should fail due to authentication middleware
    $response->assertStatus(401);
});
test('wallet initialization requires valid data', function (): void {
    $response = $this->postJson('/api/v1/wallet/wallets/initialize', [
        // Missing required fields
    ]);

    // This should fail due to authentication middleware first
    $response->assertStatus(401);
});
test('money value object works correctly', function (): void {
    $money1 = Money::fromAmount(10.50, 'KHR');
    $money2 = Money::fromAmount(5.25, 'KHR');

    expect($money1->amount())->toEqual(10.50);
    expect($money1->currency())->toEqual('KHR');
    expect($money1->isGreaterThan($money2))->toBeTrue();

    $sum = $money1->add($money2);
    expect($sum->amount())->toEqual(15.75);

    $difference = $money1->subtract($money2);
    expect($difference->amount())->toEqual(5.25);
});
test('wallet type enum works correctly', function (): void {
    $mainWallet = WalletType::MAIN;
    $commissionWallet = WalletType::COMMISSION;

    expect($mainWallet->value)->toEqual('main');
    expect($mainWallet->getLabel())->toEqual('Main Wallet');
    expect($mainWallet->canTransferTo($commissionWallet))->toBeTrue();
    expect($mainWallet->isActive())->toBeTrue();
});
test('transaction type enum works correctly', function (): void {
    $creditType = TransactionType::CREDIT;
    $debitType = TransactionType::DEBIT;

    expect($creditType->isCredit())->toBeTrue();
    expect($creditType->isDebit())->toBeFalse();
    expect($debitType->isCredit())->toBeFalse();
    expect($debitType->isDebit())->toBeTrue();
    expect($creditType->getCategory())->toEqual('general');
});
