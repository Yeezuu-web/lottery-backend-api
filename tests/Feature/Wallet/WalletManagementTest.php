<?php

namespace Tests\Feature\Wallet;

use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionType;
use App\Domain\Wallet\ValueObjects\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WalletManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Set up test data if needed
    }

    public function test_can_get_wallet_types(): void
    {
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
    }

    public function test_can_get_transaction_types(): void
    {
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
    }

    public function test_can_get_credit_transaction_types(): void
    {
        $response = $this->getJson('/api/v1/wallet/transaction-types/credit');

        $response->assertStatus(200);

        $creditTypes = $response->json('data');
        foreach ($creditTypes as $type) {
            $this->assertTrue($type['is_credit']);
            $this->assertFalse($type['is_debit']);
        }
    }

    public function test_can_get_debit_transaction_types(): void
    {
        $response = $this->getJson('/api/v1/wallet/transaction-types/debit');

        $response->assertStatus(200);

        $debitTypes = $response->json('data');
        foreach ($debitTypes as $type) {
            $this->assertFalse($type['is_credit']);
            $this->assertTrue($type['is_debit']);
        }
    }

    public function test_wallet_creation_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/wallet/wallets', [
            'owner_id' => 1,
            'wallet_type' => 'main',
            'currency' => 'KHR',
        ]);

        // This should fail due to authentication middleware
        $response->assertStatus(401);
    }

    public function test_wallet_initialization_requires_valid_data(): void
    {
        $response = $this->postJson('/api/v1/wallet/wallets/initialize', [
            // Missing required fields
        ]);

        // This should fail due to authentication middleware first
        $response->assertStatus(401);
    }

    public function test_money_value_object_works_correctly(): void
    {
        $money1 = Money::fromAmount(10.50, 'KHR');
        $money2 = Money::fromAmount(5.25, 'KHR');

        $this->assertEquals(10.50, $money1->amount());
        $this->assertEquals('KHR', $money1->currency());
        $this->assertTrue($money1->isGreaterThan($money2));

        $sum = $money1->add($money2);
        $this->assertEquals(15.75, $sum->amount());

        $difference = $money1->subtract($money2);
        $this->assertEquals(5.25, $difference->amount());
    }

    public function test_wallet_type_enum_works_correctly(): void
    {
        $mainWallet = WalletType::MAIN;
        $commissionWallet = WalletType::COMMISSION;

        $this->assertEquals('main', $mainWallet->value);
        $this->assertEquals('Main Wallet', $mainWallet->getLabel());
        $this->assertTrue($mainWallet->canTransferTo($commissionWallet));
        $this->assertTrue($mainWallet->isActive());
    }

    public function test_transaction_type_enum_works_correctly(): void
    {
        $creditType = TransactionType::CREDIT;
        $debitType = TransactionType::DEBIT;

        $this->assertTrue($creditType->isCredit());
        $this->assertFalse($creditType->isDebit());
        $this->assertFalse($debitType->isCredit());
        $this->assertTrue($debitType->isDebit());
        $this->assertEquals('general', $creditType->getCategory());
    }
}
