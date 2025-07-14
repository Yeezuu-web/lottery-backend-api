<?php

declare(strict_types=1);

namespace App\Application\Agent\Commands;

final readonly class GetAgentsCommand
{
    public function __construct(
        private int $viewerId,
        private ?int $targetAgentId = null,
        private ?string $agentType = null,
        private ?bool $directOnly = true,
        private ?int $page = 1,
        private ?int $perPage = 20
    ) {}

    public function getViewerId(): int
    {
        return $this->viewerId;
    }

    public function getTargetAgentId(): ?int
    {
        return $this->targetAgentId;
    }

    public function getAgentType(): ?string
    {
        return $this->agentType;
    }

    public function isDirectOnly(): bool
    {
        return $this->directOnly ?? true;
    }

    public function getPage(): int
    {
        return $this->page ?? 1;
    }

    public function getPerPage(): int
    {
        return $this->perPage ?? 20;
    }

    public function toArray(): array
    {
        return [
            'viewer_id' => $this->viewerId,
            'target_agent_id' => $this->targetAgentId,
            'agent_type' => $this->agentType,
            'direct_only' => $this->directOnly,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
