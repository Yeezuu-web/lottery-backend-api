<?php

declare(strict_types=1);

namespace App\Infrastructure\AgentSettings\Repositories;

use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Domain\AgentSettings\Models\AgentSettings;
use App\Domain\AgentSettings\ValueObjects\DailyLimit;
use App\Domain\AgentSettings\ValueObjects\NumberLimit;
use App\Infrastructure\AgentSettings\Models\EloquentAgentSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class AgentSettingsRepository implements AgentSettingsRepositoryInterface
{
    private const CACHE_PREFIX = 'agent_settings';

    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private EloquentAgentSettings $model
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
            ->where('agent_id', $agentId)
            ->first();

        if (! $eloquentSettings) {
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

        if (! $eloquentSettings) {
            $eloquentSettings = new EloquentAgentSettings;
        }

        $eloquentSettings->fill([
            'agent_id' => $agentSettings->getAgentId(),
            'daily_limit' => $agentSettings->getDailyLimit()->isUnlimited()
                ? null
                : $agentSettings->getDailyLimit()->getLimit(),
            'max_commission' => $agentSettings->getMaxCommission(),
            'max_share' => $agentSettings->getMaxShare(),
            'number_limits' => $agentSettings->getNumberLimitsArray(),
            'blocked_numbers' => $agentSettings->getBlockedNumbers(),
        ]);

        $eloquentSettings->save();

        // Clear cache for this agent
        $this->clearCache($agentSettings->getAgentId());

        return $this->mapFromEloquent($eloquentSettings);
    }

    public function delete(int $agentId): bool
    {
        $this->clearCache($agentId);

        return $this->model->where('agent_id', $agentId)->delete() > 0;
    }

    public function getDailyUsage(int $agentId): int
    {
        $today = now()->format('Y-m-d');

        $usage = DB::table('daily_limit_usage')
            ->where('agent_id', $agentId)
            ->where('date', $today)
            ->first();

        return $usage ? (int) $usage->total_amount : 0;
    }

    public function getNumberUsage(int $agentId): array
    {
        $today = now()->format('Y-m-d');

        $usages = DB::table('number_limit_usage')
            ->where('agent_id', $agentId)
            ->where('date', $today)
            ->get();

        $result = [];
        foreach ($usages as $usage) {
            $result[$usage->game_type][$usage->number] = (int) $usage->total_amount;
        }

        return $result;
    }

    public function incrementDailyUsage(int $agentId, int $amount): void
    {
        $today = now()->format('Y-m-d');

        DB::table('daily_limit_usage')
            ->updateOrInsert(
                ['agent_id' => $agentId, 'date' => $today],
                [
                    'total_amount' => DB::raw('COALESCE(total_amount, 0) + '.$amount),
                    'last_updated_at' => now(),
                ]
            );
    }

    public function incrementNumberUsage(int $agentId, string $gameType, string $number, int $amount): void
    {
        $today = now()->format('Y-m-d');

        DB::table('number_limit_usage')
            ->updateOrInsert(
                [
                    'agent_id' => $agentId,
                    'number' => $number,
                    'game_type' => $gameType,
                    'date' => $today,
                ],
                [
                    'total_amount' => DB::raw('COALESCE(total_amount, 0) + '.$amount),
                    'last_updated_at' => now(),
                ]
            );
    }

    public function getActiveSettings(): array
    {
        $eloquentSettings = $this->model
            ->where('is_active', true)
            ->get();

        return $eloquentSettings->map(fn ($settings): AgentSettings => $this->mapFromEloquent($settings))->toArray();
    }

    public function hasSettings(int $agentId): bool
    {
        return $this->model->where('agent_id', $agentId)->exists();
    }

    private function mapFromEloquent(EloquentAgentSettings $eloquentSettings): AgentSettings
    {
        // Create daily limit value object
        $dailyLimit = DailyLimit::create($eloquentSettings->daily_limit);

        // Create number limits value objects
        $numberLimits = [];
        if ($eloquentSettings->number_limits) {
            foreach ($eloquentSettings->number_limits as $gameType => $limits) {
                foreach ($limits as $number => $limit) {
                    $numberLimits[] = NumberLimit::create($gameType, $number, $limit);
                }
            }
        }

        return AgentSettings::create(
            agentId: $eloquentSettings->agent_id,
            dailyLimit: $dailyLimit,
            maxCommission: (float) $eloquentSettings->max_commission,
            maxShare: (float) $eloquentSettings->max_share,
            numberLimits: $numberLimits,
            blockedNumbers: $eloquentSettings->blocked_numbers ?? []
        );
    }

    private function getCacheKey(int $agentId): string
    {
        return self::CACHE_PREFIX.':'.$agentId;
    }

    private function clearCache(int $agentId): void
    {
        Cache::forget($this->getCacheKey($agentId));
    }

    private function serializeForCache(AgentSettings $agentSettings): array
    {
        return [
            'agent_id' => $agentSettings->getAgentId(),
            'daily_limit' => $agentSettings->getDailyLimit()->isUnlimited()
                ? null
                : $agentSettings->getDailyLimit()->getLimit(),
            'max_commission' => $agentSettings->getMaxCommission(),
            'max_share' => $agentSettings->getMaxShare(),
            'number_limits' => $agentSettings->getNumberLimitsArray(),
            'blocked_numbers' => $agentSettings->getBlockedNumbers(),
        ];
    }

    private function deserializeFromCache(array $data): AgentSettings
    {
        // Create daily limit value object
        $dailyLimit = DailyLimit::create($data['daily_limit']);

        // Create number limits value objects
        $numberLimits = [];
        if ($data['number_limits']) {
            foreach ($data['number_limits'] as $gameType => $limits) {
                foreach ($limits as $number => $limit) {
                    $numberLimits[] = NumberLimit::create($gameType, $number, $limit);
                }
            }
        }

        return AgentSettings::create(
            agentId: $data['agent_id'],
            dailyLimit: $dailyLimit,
            maxCommission: $data['max_commission'],
            maxShare: $data['max_share'],
            numberLimits: $numberLimits,
            blockedNumbers: $data['blocked_numbers'] ?? []
        );
    }
}
