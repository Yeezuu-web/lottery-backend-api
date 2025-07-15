<?php

declare(strict_types=1);

namespace App\Application\Agent\Listeners;

use App\Application\Wallet\Contracts\WalletServiceInterface;
use App\Domain\Agent\Events\AgentCreated;
use Illuminate\Support\Facades\Log;

final readonly class CreateWalletsOnAgentCreated
{
    public function __construct(
        private WalletServiceInterface $walletService
    ) {}

    public function handle(AgentCreated $event): void
    {
        Log::info('Auto-creating wallets for new agent', [
            'agent_id' => $event->agent->id(),
            'username' => $event->agent->username()->value(),
            'agent_type' => $event->agent->agentType()->value(),
        ]);

        // Initialize wallets for the new agent
        $result = $this->walletService->initializeWalletsForOwner(
            ownerId: $event->agent->id(),
            currency: 'KHR' // Default currency, could be configurable
        );

        if ($result->success) {
            Log::info('Wallets created successfully for new agent', [
                'agent_id' => $event->agent->id(),
                'username' => $event->agent->username()->value(),
                'wallets' => array_keys($result->data),
            ]);
        } else {
            Log::error('Failed to create wallets for new agent', [
                'agent_id' => $event->agent->id(),
                'username' => $event->agent->username()->value(),
                'error' => $result->message,
                'details' => $result->errors,
            ]);

            // In a production system, you might want to:
            // - Queue a retry job
            // - Send an alert to administrators
            // - Create a manual task for wallet creation
        }
    }
}
