<?php

namespace App\Infrastructure\AgentSettings\Repositories;

use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Domain\AgentSettings\Models\AgentSettings;
use App\Domain\AgentSettings\ValueObjects\CommissionRate;
use App\Domain\AgentSettings\ValueObjects\CommissionSharingSettings;
use App\Domain\AgentSettings\ValueObjects\PayoutProfile;
use App\Domain\AgentSettings\ValueObjects\SharingRate;
use App\Infrastructure\AgentSettings\Models\EloquentAgentSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class AgentSettingsRepository implements AgentSettingsRepositoryInterface
{
    private const CACHE_PREFIX = 'agent_settings';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly EloquentAgentSettings $model
    ) {}

    public function findByAgentId(int $agentId): ?AgentSettings
    {
        $cacheKey = $this->getCacheKey($agentId);

        // Try to get from cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $this->deserializeFromCache($cached);
        }

        // If not in cache, get from database
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->where('agent_id', $agentId)
            ->first();

        if (!$eloquentSettings) {
            return null;
        }

        $agentSettings = $this->mapFromEloquent($eloquentSettings);

        // Cache the result
        Cache::put($cacheKey, $this->serializeForCache($agentSettings), self::CACHE_TTL);

        return $agentSettings;
    }

    public function save(AgentSettings $agentSettings): AgentSettings
    {
        $eloquentSettings = $this->model
            ->where('agent_id', $agentSettings->getAgentId())
            ->first();

        if (!$eloquentSettings) {
            $eloquentSettings = new EloquentAgentSettings();
        }

        $eloquentSettings->fill([
            'agent_id' => $agentSettings->getAgentId(),
            'payout_profile' => $agentSettings->getPayoutProfile()?->toArray(),
            'payout_profile_source_agent_id' => $agentSettings->getAgentId(), // Default to same agent
            'has_custom_payout_profile' => $agentSettings->hasCustomPayoutProfile(),
            'commission_rate' => $agentSettings->getCommissionSharingSettings()->getCommissionRate()?->getRate(),
            'sharing_rate' => $agentSettings->getCommissionSharingSettings()->getSharingRate()?->getRate(),
            'max_commission_sharing_rate' => $agentSettings->getCommissionSharingSettings()->getMaxCombinedRate(),
            'effective_payout_profile' => $agentSettings->getEffectivePayoutProfile()->toArray(),
            'effective_payout_source_agent_id' => $agentSettings->getAgentId(), // Default to same agent
            'effective_commission_rate' => $agentSettings->getEffectiveCommissionSharingSettings()->getCommissionRate()?->getRate(),
            'effective_sharing_rate' => $agentSettings->getEffectiveCommissionSharingSettings()->getSharingRate()?->getRate(),
            'is_computed' => $agentSettings->isComputed(),
            'computed_at' => $agentSettings->getComputedAt(),
            'cache_expires_at' => $agentSettings->getCacheExpiresAt(),
            'betting_limits' => $agentSettings->getBettingLimits(),
            'blocked_numbers' => $agentSettings->getBlockedNumbers(),
            'auto_settlement' => $agentSettings->getAutoSettlement(),
            'is_active' => $agentSettings->isActive(),
        ]);

        $eloquentSettings->save();

        // Clear cache for this agent
        $this->clearCache($agentSettings->getAgentId());

        return $this->mapFromEloquent($eloquentSettings);
    }

    public function delete(int $agentId): bool
    {
        $deleted = $this->model
            ->where('agent_id', $agentId)
            ->delete();

        if ($deleted) {
            $this->clearCache($agentId);
        }

        return $deleted > 0;
    }

    public function exists(int $agentId): bool
    {
        return $this->model
            ->where('agent_id', $agentId)
            ->exists();
    }

    public function findByAgentIds(array $agentIds): array
    {
        if (empty($agentIds)) {
            return [];
        }

        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->whereIn('agent_id', $agentIds)
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function findWithExpiredCache(): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->withExpiredCache()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function refreshCache(int $agentId): ?AgentSettings
    {
        $this->clearCache($agentId);
        return $this->findByAgentId($agentId);
    }

    public function getAllActive(): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function getInheritanceChain(int $agentId): array
    {
        // Get the agent and traverse up the hierarchy
        $eloquentSettings = $this->model
            ->with(['agent.parent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->where('agent_id', $agentId)
            ->first();

        if (!$eloquentSettings || !$eloquentSettings->agent) {
            return [];
        }

        $chain = [];
        $currentAgent = $eloquentSettings->agent;

        while ($currentAgent) {
            $agentSettings = $this->findByAgentId($currentAgent->id);
            if ($agentSettings) {
                $chain[] = $agentSettings;
            }
            $currentAgent = $currentAgent->parent;
        }

        return $chain;
    }

    public function findWithCustomPayoutProfile(): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->withCustomPayoutProfile()
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function findWithInheritedPayoutProfile(): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->withInheritedPayoutProfile()
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function findWithCommissionRateAbove(float $threshold): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->withCommissionRateAbove($threshold)
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function findWithSharingRateAbove(float $threshold): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->withSharingRateAbove($threshold)
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function findWithAutoSettlement(): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->withAutoSettlement()
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function findComputed(): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->computed()
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function findNotComputed(): array
    {
        $eloquentSettings = $this->model
            ->with(['agent', 'payoutProfileSource', 'effectivePayoutSource'])
            ->notComputed()
            ->active()
            ->get();

        return $eloquentSettings->map(fn($settings) => $this->mapFromEloquent($settings))->toArray();
    }

    public function updateCacheExpiration(int $agentId, Carbon $expiresAt): bool
    {
        $updated = $this->model
            ->where('agent_id', $agentId)
            ->update([
                'cache_expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->clearCache($agentId);
        }

        return $updated > 0;
    }

    public function markAsComputed(int $agentId, Carbon $computedAt): bool
    {
        $updated = $this->model
            ->where('agent_id', $agentId)
            ->update([
                'is_computed' => true,
                'computed_at' => $computedAt,
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->clearCache($agentId);
        }

        return $updated > 0;
    }

    public function bulkUpdateCacheExpiration(array $agentIds, Carbon $expiresAt): int
    {
        if (empty($agentIds)) {
            return 0;
        }

        $updated = $this->model
            ->whereIn('agent_id', $agentIds)
            ->update([
                'cache_expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

        // Clear caches for all updated agents
        foreach ($agentIds as $agentId) {
            $this->clearCache($agentId);
        }

        return $updated;
    }

    private function mapFromEloquent(EloquentAgentSettings $eloquentSettings): AgentSettings
    {
        $payoutProfile = $eloquentSettings->payout_profile
            ? PayoutProfile::fromArray($eloquentSettings->payout_profile)
            : null;

        $effectivePayoutProfile = $eloquentSettings->effective_payout_profile
            ? PayoutProfile::fromArray($eloquentSettings->effective_payout_profile)
            : PayoutProfile::default();

        $commissionRate = $eloquentSettings->commission_rate !== null
            ? CommissionRate::fromPercentage($eloquentSettings->commission_rate)
            : null;

        $sharingRate = $eloquentSettings->sharing_rate !== null
            ? SharingRate::fromPercentage($eloquentSettings->sharing_rate)
            : null;

        $effectiveCommissionRate = $eloquentSettings->effective_commission_rate !== null
            ? CommissionRate::fromPercentage($eloquentSettings->effective_commission_rate)
            : null;

        $effectiveSharingRate = $eloquentSettings->effective_sharing_rate !== null
            ? SharingRate::fromPercentage($eloquentSettings->effective_sharing_rate)
            : null;

        $commissionSharingSettings = new CommissionSharingSettings(
            $commissionRate,
            $sharingRate,
            $eloquentSettings->max_commission_sharing_rate ?? 50.0
        );

        $effectiveCommissionSharingSettings = new CommissionSharingSettings(
            $effectiveCommissionRate,
            $effectiveSharingRate,
            $eloquentSettings->max_commission_sharing_rate ?? 50.0
        );

        return new AgentSettings(
            agentId: $eloquentSettings->agent_id,
            payoutProfile: $payoutProfile,
            payoutProfileSourceAgentId: $eloquentSettings->payout_profile_source_agent_id,
            hasCustomPayoutProfile: $eloquentSettings->has_custom_payout_profile,
            commissionSharingSettings: $commissionSharingSettings,
            effectivePayoutProfile: $effectivePayoutProfile,
            effectivePayoutSourceAgentId: $eloquentSettings->effective_payout_source_agent_id ?? $eloquentSettings->agent_id,
            effectiveCommissionSharingSettings: $effectiveCommissionSharingSettings,
            isComputed: $eloquentSettings->is_computed,
            computedAt: $eloquentSettings->computed_at ? Carbon::parse($eloquentSettings->computed_at) : null,
            cacheExpiresAt: $eloquentSettings->cache_expires_at ? Carbon::parse($eloquentSettings->cache_expires_at) : null,
            bettingLimits: $eloquentSettings->betting_limits ?? [],
            blockedNumbers: $eloquentSettings->blocked_numbers ?? [],
            autoSettlement: $eloquentSettings->auto_settlement ?? false,
            isActive: $eloquentSettings->is_active ?? true,
            createdAt: $eloquentSettings->created_at ? Carbon::parse($eloquentSettings->created_at) : Carbon::now(),
            updatedAt: $eloquentSettings->updated_at ? Carbon::parse($eloquentSettings->updated_at) : Carbon::now()
        );
    }

    private function getCacheKey(int $agentId): string
    {
        return self::CACHE_PREFIX . ':' . $agentId;
    }

    private function clearCache(int $agentId): void
    {
        Cache::forget($this->getCacheKey($agentId));
    }

    private function serializeForCache(AgentSettings $agentSettings): array
    {
        return [
            'agent_id' => $agentSettings->getAgentId(),
            'payout_profile' => $agentSettings->getPayoutProfile()?->toArray(),
            'payout_profile_source_agent_id' => $agentSettings->getPayoutProfileSourceAgentId(),
            'has_custom_payout_profile' => $agentSettings->hasCustomPayoutProfile(),
            'commission_rate' => $agentSettings->getCommissionRate()?->value(),
            'sharing_rate' => $agentSettings->getSharingRate()?->value(),
            'max_commission_sharing_rate' => $agentSettings->getMaxCommissionSharingRate(),
            'effective_payout_profile' => $agentSettings->getEffectivePayoutProfile()?->toArray(),
            'effective_payout_source_agent_id' => $agentSettings->getEffectivePayoutSourceAgentId(),
            'effective_commission_rate' => $agentSettings->getEffectiveCommissionRate()?->value(),
            'effective_sharing_rate' => $agentSettings->getEffectiveSharingRate()?->value(),
            'is_computed' => $agentSettings->isComputed(),
            'computed_at' => $agentSettings->getComputedAt()?->toISOString(),
            'cache_expires_at' => $agentSettings->getCacheExpiresAt()?->toISOString(),
            'betting_limits' => $agentSettings->getBettingLimits(),
            'blocked_numbers' => $agentSettings->getBlockedNumbers(),
            'auto_settlement' => $agentSettings->hasAutoSettlement(),
            'is_active' => $agentSettings->isActive(),
            'created_at' => $agentSettings->getCreatedAt()->toISOString(),
            'updated_at' => $agentSettings->getUpdatedAt()->toISOString(),
        ];
    }

    private function deserializeFromCache(array $cached): AgentSettings
    {
        $payoutProfile = $cached['payout_profile']
            ? PayoutProfile::fromArray($cached['payout_profile'])
            : null;

        $effectivePayoutProfile = $cached['effective_payout_profile']
            ? PayoutProfile::fromArray($cached['effective_payout_profile'])
            : null;

        $commissionRate = $cached['commission_rate'] !== null
            ? CommissionRate::fromFloat($cached['commission_rate'])
            : null;

        $sharingRate = $cached['sharing_rate'] !== null
            ? SharingRate::fromFloat($cached['sharing_rate'])
            : null;

        $effectiveCommissionRate = $cached['effective_commission_rate'] !== null
            ? CommissionRate::fromFloat($cached['effective_commission_rate'])
            : null;

        $effectiveSharingRate = $cached['effective_sharing_rate'] !== null
            ? SharingRate::fromFloat($cached['effective_sharing_rate'])
            : null;

        $commissionSharingSettings = CommissionSharingSettings::create(
            $commissionRate,
            $sharingRate,
            $cached['max_commission_sharing_rate'],
            $payoutProfile
        );

        return AgentSettings::create(
            agentId: $cached['agent_id'],
            payoutProfile: $payoutProfile,
            payoutProfileSourceAgentId: $cached['payout_profile_source_agent_id'],
            hasCustomPayoutProfile: $cached['has_custom_payout_profile'],
            commissionSharingSettings: $commissionSharingSettings,
            effectivePayoutProfile: $effectivePayoutProfile,
            effectivePayoutSourceAgentId: $cached['effective_payout_source_agent_id'],
            effectiveCommissionRate: $effectiveCommissionRate,
            effectiveSharingRate: $effectiveSharingRate,
            isComputed: $cached['is_computed'],
            computedAt: $cached['computed_at'] ? Carbon::parse($cached['computed_at']) : null,
            cacheExpiresAt: $cached['cache_expires_at'] ? Carbon::parse($cached['cache_expires_at']) : null,
            bettingLimits: $cached['betting_limits'] ?? [],
            blockedNumbers: $cached['blocked_numbers'] ?? [],
            autoSettlement: $cached['auto_settlement'],
            isActive: $cached['is_active'],
            createdAt: Carbon::parse($cached['created_at']),
            updatedAt: Carbon::parse($cached['updated_at'])
        );
    }
}
