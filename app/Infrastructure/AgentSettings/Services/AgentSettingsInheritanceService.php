<?php

namespace App\Infrastructure\AgentSettings\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Domain\AgentSettings\Models\AgentSettings;
use App\Domain\AgentSettings\ValueObjects\PayoutProfile;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\AgentSettings\ValueObjects\CommissionSharingSettings;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;

final class AgentSettingsInheritanceService
{
    private const MAX_INHERITANCE_DEPTH = 10;

    public function __construct(
        private readonly AgentSettingsRepositoryInterface $repository
    ) {}

    /**
     * Compute effective settings for an agent by inheriting from parent hierarchy
     */
    public function computeEffectiveSettings(int $agentId): AgentSettings
    {
        $agentSettings = $this->repository->findByAgentId($agentId);

        if (! $agentSettings) {
            throw AgentSettingsException::notFound($agentId);
        }

        // If agent has custom settings and they're not expired, return as-is
        if ($agentSettings->hasCustomPayoutProfile() && ! $agentSettings->isCacheExpired()) {
            return $agentSettings;
        }

        // Get the inheritance chain
        $inheritanceChain = $this->getInheritanceChain($agentId);

        // Find the nearest parent with custom settings
        $inheritedProfile = null;
        $inheritedSettings = null;
        $sourceAgentId = $agentId;

        foreach ($inheritanceChain as $parentId) {
            $parentSettings = $this->repository->findByAgentId($parentId);

            if ($parentSettings && $parentSettings->hasCustomPayoutProfile()) {
                $inheritedProfile = $parentSettings->getEffectivePayoutProfile();
                $inheritedSettings = $parentSettings->getEffectiveCommissionSharingSettings();
                $sourceAgentId = $parentId;
                break;
            }
        }

        // If no parent has custom settings, use default
        if (! $inheritedProfile) {
            $inheritedProfile = PayoutProfile::default();
            $inheritedSettings = CommissionSharingSettings::default();
        }

        // Apply inheritance rules and constraints
        $effectiveSettings = $this->applyInheritanceRules(
            $agentSettings,
            $inheritedProfile,
            $inheritedSettings,
            $sourceAgentId
        );

        // Mark as computed and save
        $computedSettings = $effectiveSettings->markAsComputed(
            $inheritedProfile,
            $sourceAgentId,
            $inheritedSettings
        );

        return $this->repository->save($computedSettings);
    }

    /**
     * Get the inheritance chain for an agent (parent IDs in order)
     */
    public function getInheritanceChain(int $agentId): array
    {
        $chain = [];
        $currentId = $agentId;
        $depth = 0;

        while ($depth < self::MAX_INHERITANCE_DEPTH) {
            // Get parent agent ID from agents table
            $parentId = DB::table('agents')
                ->where('id', $currentId)
                ->value('parent_agent_id');

            if (! $parentId) {
                break;
            }

            // Check for circular inheritance
            if (in_array($parentId, $chain)) {
                throw AgentSettingsException::circularInheritanceDetected($agentId, $chain);
            }

            $chain[] = $parentId;
            $currentId = $parentId;
            $depth++;
        }

        if ($depth >= self::MAX_INHERITANCE_DEPTH) {
            throw AgentSettingsException::hierarchyDepthExceeded(self::MAX_INHERITANCE_DEPTH);
        }

        return $chain;
    }

    /**
     * Apply inheritance rules when computing effective settings
     */
    private function applyInheritanceRules(
        AgentSettings $agentSettings,
        PayoutProfile $inheritedProfile,
        CommissionSharingSettings $inheritedSettings,
        int $sourceAgentId
    ): AgentSettings {
        // Start with the inherited profile
        $effectiveProfile = $inheritedProfile;

        // If agent has custom profile, use it instead
        if ($agentSettings->hasCustomPayoutProfile()) {
            $effectiveProfile = $agentSettings->getPayoutProfile();
        }

        // For commission/sharing rates, apply constraints
        $agentCommissionRate = $agentSettings->getCommissionSharingSettings()->getCommissionRateValue();
        $agentSharingRate = $agentSettings->getCommissionSharingSettings()->getSharingRateValue();

        $inheritedCommissionRate = $inheritedSettings->getCommissionRateValue();
        $inheritedSharingRate = $inheritedSettings->getSharingRateValue();

        // Agent's commission cannot exceed parent's commission
        $effectiveCommissionRate = min($agentCommissionRate, $inheritedCommissionRate);

        // Agent's sharing should not exceed parent's sharing
        $effectiveSharingRate = min($agentSharingRate, $inheritedSharingRate);

        // Validate that total doesn't exceed profile limits
        $maxAllowed = $effectiveProfile->getMaxCommissionSharingRate();
        $total = $effectiveCommissionRate + $effectiveSharingRate;

        if ($total > $maxAllowed) {
            // Prioritize commission over sharing and adjust
            if ($effectiveCommissionRate > $maxAllowed) {
                $effectiveCommissionRate = $maxAllowed;
                $effectiveSharingRate = 0;
            } else {
                $effectiveSharingRate = $maxAllowed - $effectiveCommissionRate;
            }
        }

        // Create effective settings
        $effectiveCommissionSharingSettings = CommissionSharingSettings::fromPayoutProfile(
            $effectiveCommissionRate > 0 ? $effectiveCommissionRate : null,
            $effectiveSharingRate > 0 ? $effectiveSharingRate : null,
            $effectiveProfile
        );

        // Return updated agent settings
        return new AgentSettings(
            agentId: $agentSettings->getAgentId(),
            payoutProfile: $agentSettings->getPayoutProfile(),
            payoutProfileSourceAgentId: $agentSettings->getPayoutProfile() ? $agentSettings->getAgentId() : null,
            hasCustomPayoutProfile: $agentSettings->hasCustomPayoutProfile(),
            commissionSharingSettings: $agentSettings->getCommissionSharingSettings(),
            effectivePayoutProfile: $effectiveProfile,
            effectivePayoutSourceAgentId: $sourceAgentId,
            effectiveCommissionSharingSettings: $effectiveCommissionSharingSettings,
            isComputed: false, // Will be marked as computed later
            computedAt: $agentSettings->getComputedAt(),
            cacheExpiresAt: $agentSettings->getCacheExpiresAt(),
            bettingLimits: $agentSettings->getBettingLimits(),
            blockedNumbers: $agentSettings->getBlockedNumbers(),
            autoSettlement: $agentSettings->getAutoSettlement(),
            isActive: $agentSettings->isActive()
        );
    }

    /**
     * Batch compute effective settings for multiple agents
     */
    public function batchComputeEffectiveSettings(array $agentIds): array
    {
        $results = [];

        foreach ($agentIds as $agentId) {
            try {
                $results[$agentId] = $this->computeEffectiveSettings($agentId);
            } catch (AgentSettingsException $e) {
                // Log error but continue with other agents
                Log::warning("Failed to compute settings for agent {$agentId}: ".$e->getMessage());
                $results[$agentId] = null;
            }
        }

        return $results;
    }

    /**
     * Refresh expired cache for all agents
     */
    public function refreshExpiredCaches(): int
    {
        $expiredSettings = $this->repository->findWithExpiredCache();
        $refreshedCount = 0;

        foreach ($expiredSettings as $settings) {
            try {
                $this->computeEffectiveSettings($settings->getAgentId());
                $refreshedCount++;
            } catch (AgentSettingsException $e) {
                Log::warning("Failed to refresh cache for agent {$settings->getAgentId()}: ".$e->getMessage());
            }
        }

        return $refreshedCount;
    }
}
