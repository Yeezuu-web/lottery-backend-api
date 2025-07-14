<?php

namespace Tests\Feature\AgentSettings;

use App\Application\AgentSettings\Commands\CreateAgentSettingsCommand;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\UseCases\CreateAgentSettingsUseCase;
use App\Domain\AgentSettings\ValueObjects\CommissionRate;
use App\Domain\AgentSettings\ValueObjects\CommissionSharingSettings;
use App\Domain\AgentSettings\ValueObjects\PayoutProfile;
use App\Domain\AgentSettings\ValueObjects\SharingRate;
use App\Infrastructure\AgentSettings\Repositories\AgentSettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private AgentSettingsRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(AgentSettingsRepositoryInterface::class);
    }

    public function test_commission_rate_value_object_creation()
    {
        $commissionRate = CommissionRate::fromPercentage(5.0);

        $this->assertEquals(5.0, $commissionRate->getRate());
        $this->assertFalse($commissionRate->isZero());
        $this->assertEquals(500.0, $commissionRate->calculateAmount(10000));
    }

    public function test_sharing_rate_value_object_creation()
    {
        $sharingRate = SharingRate::fromPercentage(2.0);

        $this->assertEquals(2.0, $sharingRate->getRate());
        $this->assertFalse($sharingRate->isZero());
        $this->assertEquals(200.0, $sharingRate->calculateAmount(10000));
    }

    public function test_commission_sharing_settings_commission_only()
    {
        $settings = CommissionSharingSettings::commissionOnly(5.0);

        $this->assertTrue($settings->hasCommission());
        $this->assertFalse($settings->hasSharing());
        $this->assertEquals(5.0, $settings->getCommissionRateValue());
        $this->assertEquals(0.0, $settings->getSharingRateValue());
        $this->assertEquals(5.0, $settings->getTotalRate());
    }

    public function test_commission_sharing_settings_sharing_only()
    {
        $settings = CommissionSharingSettings::sharingOnly(3.0);

        $this->assertFalse($settings->hasCommission());
        $this->assertTrue($settings->hasSharing());
        $this->assertEquals(0.0, $settings->getCommissionRateValue());
        $this->assertEquals(3.0, $settings->getSharingRateValue());
        $this->assertEquals(3.0, $settings->getTotalRate());
    }

    public function test_commission_sharing_settings_both()
    {
        $settings = CommissionSharingSettings::both(5.0, 2.0);

        $this->assertTrue($settings->hasCommission());
        $this->assertTrue($settings->hasSharing());
        $this->assertTrue($settings->hasBoth());
        $this->assertEquals(5.0, $settings->getCommissionRateValue());
        $this->assertEquals(2.0, $settings->getSharingRateValue());
        $this->assertEquals(7.0, $settings->getTotalRate());
    }

    public function test_commission_sharing_settings_none()
    {
        $settings = CommissionSharingSettings::none();

        $this->assertFalse($settings->hasCommission());
        $this->assertFalse($settings->hasSharing());
        $this->assertFalse($settings->hasEither());
        $this->assertEquals(0.0, $settings->getTotalRate());
    }

    public function test_commission_sharing_settings_with_payout_profile()
    {
        $payoutProfile = PayoutProfile::default();
        $settings = CommissionSharingSettings::fromPayoutProfile(5.0, 2.0, $payoutProfile);

        $this->assertTrue($settings->hasCommission());
        $this->assertTrue($settings->hasSharing());
        $this->assertEquals(7.0, $settings->getTotalRate());
        $this->assertEquals(43.0, $settings->getRemainingCapacity()); // 50 - 7
    }

    public function test_commission_sharing_settings_exceeds_limit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total commission and sharing rate');

        CommissionSharingSettings::both(30.0, 25.0); // Total 55% exceeds default 50%
    }

    public function test_create_agent_settings_use_case()
    {
        // Use existing database structure from migrations
        DB::table('agents')->insert([
            'id' => 1,
            'username' => 'TESTUSER1',
            'email' => 'test@example.com',
            'name' => 'Test Agent',
            'agent_type' => 'agent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $useCase = new CreateAgentSettingsUseCase($this->repository);
        $command = new CreateAgentSettingsCommand(
            agentId: 1,
            commissionRate: 5.0,
            sharingRate: 2.0
        );

        $result = $useCase->execute($command);

        $this->assertTrue($result->success);
        $this->assertEquals('Agent settings created successfully', $result->message);
        $this->assertNotNull($result->data);
        $this->assertEquals(1, $result->data->agentId);
    }

    public function test_commission_sharing_settings_calculations()
    {
        $settings = CommissionSharingSettings::both(5.0, 2.0);
        $turnover = 10000.0;

        $commissionAmount = $settings->calculateCommissionAmount($turnover);
        $sharingAmount = $settings->calculateSharingAmount($turnover);
        $totalAmount = $settings->calculateTotalAmount($turnover);

        $this->assertEquals(500.0, $commissionAmount); // 5% of 10000
        $this->assertEquals(200.0, $sharingAmount);    // 2% of 10000
        $this->assertEquals(700.0, $totalAmount);      // 7% of 10000
    }

    public function test_commission_sharing_settings_rate_updates()
    {
        $settings = CommissionSharingSettings::both(5.0, 2.0);

        // Update commission rate
        $updatedSettings = $settings->withCommissionRate(3.0);
        $this->assertEquals(3.0, $updatedSettings->getCommissionRateValue());
        $this->assertEquals(2.0, $updatedSettings->getSharingRateValue());

        // Update sharing rate
        $updatedSettings = $settings->withSharingRate(4.0);
        $this->assertEquals(5.0, $updatedSettings->getCommissionRateValue());
        $this->assertEquals(4.0, $updatedSettings->getSharingRateValue());

        // Remove commission rate
        $updatedSettings = $settings->withCommissionRate(null);
        $this->assertFalse($updatedSettings->hasCommission());
        $this->assertTrue($updatedSettings->hasSharing());
    }

    public function test_commission_sharing_settings_capacity_checks()
    {
        $settings = CommissionSharingSettings::both(5.0, 2.0, 10.0); // Max 10%

        $this->assertTrue($settings->canAddCommission(2.0));  // 5 + 2 + 2 = 9 < 10
        $this->assertFalse($settings->canAddCommission(4.0)); // 5 + 2 + 4 = 11 > 10

        $this->assertTrue($settings->canAddSharing(2.0));     // 5 + 2 + 2 = 9 < 10
        $this->assertFalse($settings->canAddSharing(4.0));   // 5 + 2 + 4 = 11 > 10
    }

    public function test_commission_sharing_settings_serialization()
    {
        $settings = CommissionSharingSettings::both(5.0, 2.0);
        $array = $settings->toArray();

        $this->assertArrayHasKey('commission', $array);
        $this->assertArrayHasKey('sharing', $array);
        $this->assertArrayHasKey('max_combined_rate', $array);
        $this->assertArrayHasKey('total_rate', $array);
        $this->assertArrayHasKey('has_commission', $array);
        $this->assertArrayHasKey('has_sharing', $array);
        $this->assertArrayHasKey('has_both', $array);

        $this->assertEquals(5.0, $array['commission']['rate']);
        $this->assertEquals(2.0, $array['sharing']['rate']);
        $this->assertEquals(7.0, $array['total_rate']);
        $this->assertTrue($array['has_commission']);
        $this->assertTrue($array['has_sharing']);
        $this->assertTrue($array['has_both']);
    }
}
