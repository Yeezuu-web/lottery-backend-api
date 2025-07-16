<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Responses;

use App\Domain\AgentSettings\Models\AgentSettings;
use JsonSerializable;

final readonly class AgentSettingsResponse implements JsonSerializable
{
    public function __construct(
        public int $agentId,
        public ?int $dailyLimit,
        public float $maxCommission,
        public float $maxShare,
        public array $numberLimits,
        public array $blockedNumbers,
        public ?int $dailyUsage = null,
        public array $numberUsage = []
    ) {}

    public static function fromDomain(AgentSettings $agentSettings): self
    {
        return new self(
            agentId: $agentSettings->getAgentId(),
            dailyLimit: $agentSettings->getDailyLimit()->getLimit(),
            maxCommission: $agentSettings->getMaxCommission(),
            maxShare: $agentSettings->getMaxShare(),
            numberLimits: $agentSettings->getNumberLimitsArray(),
            blockedNumbers: $agentSettings->getBlockedNumbers()
        );
    }

    public static function fromDomainWithUsage(
        AgentSettings $agentSettings,
        ?int $dailyUsage = null,
        array $numberUsage = []
    ): self {
        return new self(
            agentId: $agentSettings->getAgentId(),
            dailyLimit: $agentSettings->getDailyLimit()->getLimit(),
            maxCommission: $agentSettings->getMaxCommission(),
            maxShare: $agentSettings->getMaxShare(),
            numberLimits: $agentSettings->getNumberLimitsArray(),
            blockedNumbers: $agentSettings->getBlockedNumbers(),
            dailyUsage: $dailyUsage,
            numberUsage: $numberUsage
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'agent_id' => $this->agentId,
            'daily_limit' => $this->dailyLimit,
            'max_commission' => $this->maxCommission,
            'max_share' => $this->maxShare,
            'number_limits' => $this->numberLimits,
            'blocked_numbers' => $this->blockedNumbers,
            'daily_usage' => $this->dailyUsage,
            'number_usage' => $this->numberUsage,
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
