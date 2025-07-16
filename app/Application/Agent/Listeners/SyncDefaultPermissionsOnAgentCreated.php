<?php

declare(strict_types=1);

namespace App\Application\Agent\Listeners;

use App\Domain\Agent\Events\AgentCreated;
use App\Domain\Auth\Services\DatabaseAuthorizationService;
use Exception;

final readonly class SyncDefaultPermissionsOnAgentCreated
{
    public function __construct(
        private DatabaseAuthorizationService $authorizationService
    ) {}

    /**
     * Handle the event
     */
    public function handle(AgentCreated $event): void
    {
        try {
            $agent = $event->agent();

            // Sync default permissions based on agent type
            $this->authorizationService->syncDefaultPermissions($agent->id());

            logger()->info('Default permissions synced for agent', [
                'agent_id' => $agent->id(),
                'agent_type' => $agent->agentType()->value(),
                'username' => $agent->username()->value(),
            ]);

        } catch (Exception $exception) {
            logger()->error('Failed to sync default permissions for agent', [
                'agent_id' => $event->agent()->id(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
