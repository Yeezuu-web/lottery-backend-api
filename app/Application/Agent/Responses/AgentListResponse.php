<?php

declare(strict_types=1);

namespace App\Application\Agent\Responses;

final readonly class AgentListResponse
{
    public function __construct(
        private array $agents,
        private int $total,
        private int $page,
        private int $perPage,
        private int $totalPages,
        private array $metadata = []
    ) {}

    public static function create(
        array $agents,
        int $total,
        int $page,
        int $perPage,
        array $metadata = []
    ): self {
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        return new self(
            $agents,
            $total,
            $page,
            $perPage,
            $totalPages,
            $metadata
        );
    }

    public function getAgents(): array
    {
        return $this->agents;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'agents' => array_map(fn ($agent) => $agent->toArray(), $this->agents),
            'pagination' => [
                'total' => $this->total,
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total_pages' => $this->totalPages,
            ],
            'metadata' => $this->metadata,
        ];
    }
}
