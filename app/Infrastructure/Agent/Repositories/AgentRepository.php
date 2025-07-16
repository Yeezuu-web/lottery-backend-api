<?php

declare(strict_types=1);

namespace App\Infrastructure\Agent\Repositories;

use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Infrastructure\Agent\Models\EloquentAgent;
use Illuminate\Support\Facades\Hash;

final readonly class AgentRepository implements AgentRepositoryInterface
{
    public function __construct(
        private EloquentAgent $model
    ) {}

    public function findById(int $id): ?Agent
    {
        $eloquentAgent = $this->model
            ->with(['parent', 'children', 'settings', 'wallets'])
            ->find($id);

        return $eloquentAgent ? $this->mapToAgent($eloquentAgent) : null;
    }

    public function findByUsername(Username $username): ?Agent
    {
        $eloquentAgent = $this->model
            ->with(['parent', 'children', 'settings', 'wallets'])
            ->where('username', $username->value())
            ->first();

        return $eloquentAgent ? $this->mapToAgent($eloquentAgent) : null;
    }

    public function findByEmail(string $email): ?Agent
    {
        $eloquentAgent = $this->model
            ->with(['parent', 'children', 'settings', 'wallets'])
            ->where('email', $email)
            ->first();

        return $eloquentAgent ? $this->mapToAgent($eloquentAgent) : null;
    }

    public function getDirectDownlines(int $agentId): array
    {
        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->where('upline_id', $agentId)
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    public function getHierarchyDownlines(int $agentId): array
    {
        // Get the agent's username to find all downlines
        $agent = $this->findById($agentId);
        if (! $agent instanceof Agent) {
            return [];
        }

        $usernamePrefix = $agent->username()->value();

        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->where('username', 'LIKE', $usernamePrefix.'%')
            ->where('id', '!=', $agentId)
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    /**
     * Get direct downlines with wallet data
     */
    public function getDirectDownlinesWithWallets(int $agentId): array
    {
        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->where('upline_id', $agentId)
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($eloquentAgent): array => [
            'agent' => $this->mapToAgent($eloquentAgent),
            'wallets' => $this->extractWalletData($eloquentAgent),
        ])->toArray();
    }

    /**
     * Get hierarchy downlines with wallet data
     */
    public function getHierarchyDownlinesWithWallets(int $agentId): array
    {
        // Get the agent's username to find all downlines
        $agent = $this->findById($agentId);
        if (! $agent instanceof Agent) {
            return [];
        }

        $usernamePrefix = $agent->username()->value();

        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->where('username', 'LIKE', $usernamePrefix.'%')
            ->where('id', '!=', $agentId)
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($eloquentAgent): array => [
            'agent' => $this->mapToAgent($eloquentAgent),
            'wallets' => $this->extractWalletData($eloquentAgent),
        ])->toArray();
    }

    public function getByUplineId(int $uplineId): array
    {
        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->where('upline_id', $uplineId)
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    public function getByAgentType(AgentType $agentType): array
    {
        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->ofType($agentType->value())
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    public function usernameExists(Username $username): bool
    {
        return $this->model->where('username', $username->value())->exists();
    }

    public function emailExists(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    public function getNextAvailableUsername(AgentType $agentType, ?Username $parentUsername = null): Username
    {
        $usernameLength = $this->getUsernameLength($agentType);
        $prefix = $parentUsername instanceof Username ? $parentUsername->value() : '';

        // For members, use parent username + sequence
        if ($agentType->value() === 'member') {
            $existingUsernames = $this->model
                ->where('username', 'LIKE', $prefix.'%')
                ->where('agent_type', 'member')
                ->pluck('username')
                ->toArray();

            $nextSequence = 1;
            do {
                $newUsername = $prefix.mb_str_pad((string) $nextSequence, $usernameLength - mb_strlen($prefix), '0', STR_PAD_LEFT);
                ++$nextSequence;
            } while (in_array($newUsername, $existingUsernames));

            return Username::fromString($newUsername);
        }

        // For other agent types, use alphabetical sequence
        $existingUsernames = $this->model
            ->where('username', 'LIKE', $prefix.str_repeat('_', $usernameLength - mb_strlen($prefix)))
            ->where('agent_type', $agentType->value())
            ->pluck('username')
            ->toArray();

        $sequence = 'A';
        do {
            $newUsername = $prefix.mb_str_pad($sequence, $usernameLength - mb_strlen($prefix), 'A', STR_PAD_LEFT);
            ++$sequence;
        } while (in_array($newUsername, $existingUsernames));

        return Username::fromString($newUsername);
    }

    public function getManagedAgents(int $agentId): array
    {
        $agent = $this->findById($agentId);
        if (! $agent instanceof Agent) {
            return [];
        }

        // Company agents can manage all agents
        if ($agent->agentType()->value() === 'company') {
            $eloquentAgents = $this->model
                ->with(['parent', 'settings', 'wallets'])
                ->where('id', '!=', $agentId)
                ->active()
                ->orderBy('username')
                ->get();

            return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
        }

        // Other agents can only manage their downlines
        return $this->getHierarchyDownlines($agentId);
    }

    public function save(Agent $agent): Agent
    {
        $eloquentAgent = $this->model->find($agent->id());

        if (! $eloquentAgent) {
            $eloquentAgent = new EloquentAgent;
        }

        $eloquentAgent->fill([
            'username' => $agent->username()->value(),
            'agent_type' => $agent->agentType()->value(),
            'upline_id' => $agent->uplineId(),
            'name' => $agent->name(),
            'email' => $agent->email(),
            'status' => $agent->isActive() ? 'active' : 'inactive',
            'password' => Hash::make($agent->password()),
        ]);

        $eloquentAgent->save();

        return $this->mapToAgent($eloquentAgent);
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function getCountByType(AgentType $agentType): int
    {
        return $this->model
            ->ofType($agentType->value())
            ->active()
            ->count();
    }

    public function getActiveAgentsCount(): int
    {
        return $this->model->active()->count();
    }

    public function search(array $criteria): array
    {
        $query = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->active();

        if (isset($criteria['username_pattern'])) {
            $query->where('username', 'LIKE', $criteria['username_pattern'].'%');
        }

        if (isset($criteria['agent_type'])) {
            $query->ofType($criteria['agent_type']);
        }

        if (isset($criteria['upline_id'])) {
            $query->withUpline($criteria['upline_id']);
        }

        if (isset($criteria['email'])) {
            $query->where('email', 'LIKE', '%'.$criteria['email'].'%');
        }

        if (isset($criteria['name'])) {
            $query->where('name', 'LIKE', '%'.$criteria['name'].'%');
        }

        $eloquentAgents = $query->orderBy('username')->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    public function paginate(int $page, int $perPage, array $criteria = []): array
    {
        $query = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->active();

        // Apply criteria filters
        if (isset($criteria['username_pattern'])) {
            $query->where('username', 'LIKE', $criteria['username_pattern'].'%');
        }

        if (isset($criteria['agent_type'])) {
            $query->ofType($criteria['agent_type']);
        }

        if (isset($criteria['upline_id'])) {
            $query->withUpline($criteria['upline_id']);
        }

        if (isset($criteria['email'])) {
            $query->where('email', 'LIKE', '%'.$criteria['email'].'%');
        }

        if (isset($criteria['name'])) {
            $query->where('name', 'LIKE', '%'.$criteria['name'].'%');
        }

        $eloquentAgents = $query
            ->orderBy('username')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    public function verifyPassword(Agent $agent, string $password): bool
    {
        $eloquentAgent = $this->model->find($agent->id());

        return $eloquentAgent && Hash::check($password, $eloquentAgent->password);
    }

    public function updatePassword(Agent $agent, string $newPassword): bool
    {
        $eloquentAgent = $this->model->find($agent->id());
        if (! $eloquentAgent) {
            return false;
        }

        $eloquentAgent->password = Hash::make($newPassword);

        return $eloquentAgent->save();
    }

    public function updateStatus(Agent $agent, bool $isActive): bool
    {
        $eloquentAgent = $this->model->find($agent->id());
        if (! $eloquentAgent) {
            return false;
        }

        $eloquentAgent->status = $isActive ? 'active' : 'inactive';

        return $eloquentAgent->save();
    }

    public function getAgentsByIds(array $agentIds): array
    {
        if ($agentIds === []) {
            return [];
        }

        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->whereIn('id', $agentIds)
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    public function getAgentsWithSettings(): array
    {
        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->whereHas('settings')
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    public function getAgentsWithWallets(): array
    {
        $eloquentAgents = $this->model
            ->with(['parent', 'settings', 'wallets'])
            ->whereHas('wallets')
            ->active()
            ->orderBy('username')
            ->get();

        return $eloquentAgents->map(fn ($agent): Agent => $this->mapToAgent($agent))->toArray();
    }

    /**
     * Get wallet data from EloquentAgent relationships
     */
    public function getAgentWallets(int $agentId): array
    {
        $eloquentAgent = $this->model
            ->with(['wallets'])
            ->find($agentId);

        if (! $eloquentAgent) {
            return [];
        }

        return $eloquentAgent->wallets->map(fn ($wallet): array => [
            'id' => $wallet->id,
            'wallet_type' => $wallet->wallet_type,
            'balance' => $wallet->balance ?? 0,
            'locked_balance' => $wallet->locked_balance ?? 0,
            'currency' => $wallet->currency ?? 'USD',
            'is_active' => $wallet->is_active ?? true,
            'last_transaction_at' => $wallet->last_transaction_at,
            'created_at' => $wallet->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $wallet->updated_at?->format('Y-m-d H:i:s'),
        ])->toArray();
    }

    /**
     * Get wallet data from already loaded EloquentAgent
     */
    public function extractWalletData(EloquentAgent $eloquentAgent): array
    {
        if (! $eloquentAgent->relationLoaded('wallets')) {
            return [];
        }

        return $eloquentAgent->wallets->map(fn ($wallet): array => [
            'id' => $wallet->id,
            'wallet_type' => $wallet->wallet_type,
            'balance' => $wallet->balance ?? 0,
            'locked_balance' => $wallet->locked_balance ?? 0,
            'currency' => $wallet->currency ?? 'USD',
            'is_active' => $wallet->is_active ?? true,
            'last_transaction_at' => $wallet->last_transaction_at,
            'created_at' => $wallet->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $wallet->updated_at?->format('Y-m-d H:i:s'),
        ])->toArray();
    }

    /**
     * Map EloquentAgent to Agent domain model
     */
    private function mapToAgent(EloquentAgent $eloquentAgent): Agent
    {
        return Agent::create(
            $eloquentAgent->id,
            $eloquentAgent->username,
            $eloquentAgent->agent_type,
            $eloquentAgent->upline_id,
            $eloquentAgent->name,
            $eloquentAgent->email,
            $eloquentAgent->status,
            $eloquentAgent->status === 'active',
            $eloquentAgent->created_at ? $eloquentAgent->created_at->toImmutable() : null,
            $eloquentAgent->updated_at ? $eloquentAgent->updated_at->toImmutable() : null,
        );
    }

    /**
     * Get username length for agent type
     */
    private function getUsernameLength(AgentType $agentType): int
    {
        return match ($agentType->value()) {
            'company' => 1,
            'super_senior' => 2,
            'senior' => 3,
            'master' => 4,
            'agent' => 5,
            'member' => 11,
            default => 5,
        };
    }
}
