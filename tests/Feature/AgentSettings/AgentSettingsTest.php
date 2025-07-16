<?php

declare(strict_types=1);

use App\Application\AgentSettings\Commands\CreateAgentSettingsCommand;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\UseCases\CreateAgentSettingsUseCase;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\AgentSettings\ValueObjects\CommissionRate;
use App\Domain\AgentSettings\ValueObjects\CommissionSharingSettings;
use App\Domain\AgentSettings\ValueObjects\PayoutProfile;
use App\Domain\AgentSettings\ValueObjects\SharingRate;
use App\Infrastructure\Agent\Models\EloquentAgent;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->repository = app(AgentSettingsRepositoryInterface::class);
});
test('commission rate value object creation', function (): void {
    $commissionRate = CommissionRate::fromPercentage(5.0);

    expect($commissionRate->getRate())->toEqual(5.0);
    expect($commissionRate->isZero())->toBeFalse();
    expect($commissionRate->calculateAmount(10000))->toEqual(500.0);
});
test('sharing rate value object creation', function (): void {
    $sharingRate = SharingRate::fromPercentage(2.0);

    expect($sharingRate->getRate())->toEqual(2.0);
    expect($sharingRate->isZero())->toBeFalse();
    expect($sharingRate->calculateAmount(10000))->toEqual(200.0);
});
test('commission sharing settings commission only', function (): void {
    $settings = CommissionSharingSettings::commissionOnly(5.0);

    expect($settings->hasCommission())->toBeTrue();
    expect($settings->hasSharing())->toBeFalse();
    expect($settings->getCommissionRateValue())->toEqual(5.0);
    expect($settings->getSharingRateValue())->toEqual(0.0);
    expect($settings->getTotalRate())->toEqual(5.0);
});
test('commission sharing settings sharing only', function (): void {
    $settings = CommissionSharingSettings::sharingOnly(3.0);

    expect($settings->hasCommission())->toBeFalse();
    expect($settings->hasSharing())->toBeTrue();
    expect($settings->getCommissionRateValue())->toEqual(0.0);
    expect($settings->getSharingRateValue())->toEqual(3.0);
    expect($settings->getTotalRate())->toEqual(3.0);
});
test('commission sharing settings both', function (): void {
    $settings = CommissionSharingSettings::both(5.0, 2.0);

    expect($settings->hasCommission())->toBeTrue();
    expect($settings->hasSharing())->toBeTrue();
    expect($settings->hasBoth())->toBeTrue();
    expect($settings->getCommissionRateValue())->toEqual(5.0);
    expect($settings->getSharingRateValue())->toEqual(2.0);
    expect($settings->getTotalRate())->toEqual(7.0);
});
test('commission sharing settings none', function (): void {
    $settings = CommissionSharingSettings::none();

    expect($settings->hasCommission())->toBeFalse();
    expect($settings->hasSharing())->toBeFalse();
    expect($settings->hasEither())->toBeFalse();
    expect($settings->getTotalRate())->toEqual(0.0);
});
test('commission sharing settings with payout profile', function (): void {
    $payoutProfile = PayoutProfile::default();
    $settings = CommissionSharingSettings::fromPayoutProfile(5.0, 2.0, $payoutProfile);

    expect($settings->hasCommission())->toBeTrue();
    expect($settings->hasSharing())->toBeTrue();
    expect($settings->getTotalRate())->toEqual(7.0);
    expect($settings->getRemainingCapacity())->toEqual(43.0);
    // 50 - 7
});
test('commission sharing settings exceeds limit', function (): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Total commission and sharing rate');

    CommissionSharingSettings::both(30.0, 25.0);
    // Total 55% exceeds default 50%
});
test('create agent settings use case', function (): void {
    // Use existing database structure from migrations
    $agent = EloquentAgent::factory()->create([
        'username' => 'A',
        'password' => Hash::make('password'), // important!
        'agent_type' => AgentType::COMPANY,
        'status' => 'active',
        'is_active' => true,
        'email' => 'A@example.com',
        'name' => 'A',
    ]);

    $useCase = new CreateAgentSettingsUseCase($this->repository);
    $command = new CreateAgentSettingsCommand(
        $agent->id,
        null,
        0.0,
        0.0,
        [],
        []
    );

    $result = $useCase->execute($command);

    expect($result->success)->toBeTrue();
    expect($result->message)->toEqual('Agent settings created successfully');
    expect($result->data)->not->toBeNull();
    expect($result->data->agentId)->toEqual(1);
});
test('commission sharing settings calculations', function (): void {
    $settings = CommissionSharingSettings::both(5.0, 2.0);
    $turnover = 10000.0;

    $commissionAmount = $settings->calculateCommissionAmount($turnover);
    $sharingAmount = $settings->calculateSharingAmount($turnover);
    $totalAmount = $settings->calculateTotalAmount($turnover);

    expect($commissionAmount)->toEqual(500.0);
    // 5% of 10000
    expect($sharingAmount)->toEqual(200.0);
    // 2% of 10000
    expect($totalAmount)->toEqual(700.0);
    // 7% of 10000
});
test('commission sharing settings rate updates', function (): void {
    $settings = CommissionSharingSettings::both(5.0, 2.0);

    // Update commission rate
    $updatedSettings = $settings->withCommissionRate(3.0);
    expect($updatedSettings->getCommissionRateValue())->toEqual(3.0);
    expect($updatedSettings->getSharingRateValue())->toEqual(2.0);

    // Update sharing rate
    $updatedSettings = $settings->withSharingRate(4.0);
    expect($updatedSettings->getCommissionRateValue())->toEqual(5.0);
    expect($updatedSettings->getSharingRateValue())->toEqual(4.0);

    // Remove commission rate
    $updatedSettings = $settings->withCommissionRate(null);
    expect($updatedSettings->hasCommission())->toBeFalse();
    expect($updatedSettings->hasSharing())->toBeTrue();
});
test('commission sharing settings capacity checks', function (): void {
    $settings = CommissionSharingSettings::both(5.0, 2.0, 10.0);

    // Max 10%
    expect($settings->canAddCommission(2.0))->toBeTrue();
    // 5 + 2 + 2 = 9 < 10
    expect($settings->canAddCommission(4.0))->toBeFalse();

    // 5 + 2 + 4 = 11 > 10
    expect($settings->canAddSharing(2.0))->toBeTrue();
    // 5 + 2 + 2 = 9 < 10
    expect($settings->canAddSharing(4.0))->toBeFalse();
    // 5 + 2 + 4 = 11 > 10
});
test('commission sharing settings serialization', function (): void {
    $settings = CommissionSharingSettings::both(5.0, 2.0);
    $array = $settings->toArray();

    expect($array)->toHaveKey('commission');
    expect($array)->toHaveKey('sharing');
    expect($array)->toHaveKey('max_combined_rate');
    expect($array)->toHaveKey('total_rate');
    expect($array)->toHaveKey('has_commission');
    expect($array)->toHaveKey('has_sharing');
    expect($array)->toHaveKey('has_both');

    expect($array['commission']['rate'])->toEqual(5.0);
    expect($array['sharing']['rate'])->toEqual(2.0);
    expect($array['total_rate'])->toEqual(7.0);
    expect($array['has_commission'])->toBeTrue();
    expect($array['has_sharing'])->toBeTrue();
    expect($array['has_both'])->toBeTrue();
});
