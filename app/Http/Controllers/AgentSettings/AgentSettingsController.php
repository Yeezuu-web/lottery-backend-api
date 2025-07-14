<?php

namespace App\Http\Controllers\AgentSettings;

use App\Application\AgentSettings\Commands\CreateAgentSettingsCommand;
use App\Application\AgentSettings\Commands\UpdateAgentSettingsCommand;
use App\Application\AgentSettings\Commands\UpdateCommissionRateCommand;
use App\Application\AgentSettings\Commands\UpdateSharingRateCommand;
use App\Application\AgentSettings\Queries\GetAgentSettingsQuery;
use App\Application\AgentSettings\UseCases\CreateAgentSettingsUseCase;
use App\Application\AgentSettings\UseCases\GetAgentSettingsUseCase;
use App\Application\AgentSettings\UseCases\UpdateAgentSettingsUseCase;
use App\Application\AgentSettings\UseCases\UpdateCommissionRateUseCase;
use App\Application\AgentSettings\UseCases\UpdateSharingRateUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgentSettings\CreateAgentSettingsRequest;
use App\Http\Requests\AgentSettings\UpdateAgentSettingsRequest;
use App\Http\Requests\AgentSettings\UpdateCommissionRateRequest;
use App\Http\Requests\AgentSettings\UpdateSharingRateRequest;
use App\Traits\HttpApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentSettingsController extends Controller
{
    use HttpApiResponse;

    public function __construct(
        private readonly CreateAgentSettingsUseCase $createUseCase,
        private readonly UpdateAgentSettingsUseCase $updateUseCase,
        private readonly UpdateCommissionRateUseCase $updateCommissionUseCase,
        private readonly UpdateSharingRateUseCase $updateSharingUseCase,
        private readonly GetAgentSettingsUseCase $getUseCase
    ) {}

    /**
     * Get agent settings
     */
    public function show(int $agentId, Request $request): JsonResponse
    {
        $refreshCache = $request->boolean('refresh_cache', false);

        $query = new GetAgentSettingsQuery(
            agentId: $agentId,
            includeEffectiveSettings: true,
            refreshCache: $refreshCache
        );

        $result = $this->getUseCase->execute($query);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors,
                statusCode: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->successResponse(
            data: $result->data,
            message: $result->message
        );
    }

    /**
     * Create agent settings
     */
    public function store(CreateAgentSettingsRequest $request): JsonResponse
    {
        $command = new CreateAgentSettingsCommand(
            agentId: $request->getAgentId(),
            payoutProfile: $request->getPayoutProfile(),
            commissionRate: $request->getCommissionRate(),
            sharingRate: $request->getSharingRate(),
            bettingLimits: $request->getBettingLimits(),
            blockedNumbers: $request->getBlockedNumbers(),
            autoSettlement: $request->getAutoSettlement(),
            isActive: $request->getIsActive()
        );

        $result = $this->createUseCase->execute($command);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors,
                statusCode: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->successResponse(
            data: $result->data,
            message: $result->message,
            statusCode: 201
        );
    }

    /**
     * Update agent settings
     */
    public function update(int $agentId, UpdateAgentSettingsRequest $request): JsonResponse
    {
        $command = new UpdateAgentSettingsCommand(
            agentId: $agentId,
            payoutProfile: $request->getPayoutProfile(),
            commissionRate: $request->getCommissionRate(),
            sharingRate: $request->getSharingRate(),
            bettingLimits: $request->getBettingLimits(),
            blockedNumbers: $request->getBlockedNumbers(),
            autoSettlement: $request->getAutoSettlement(),
            isActive: $request->getIsActive()
        );

        $result = $this->updateUseCase->execute($command);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors,
                statusCode: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->successResponse(
            data: $result->data,
            message: $result->message
        );
    }

    /**
     * Update commission rate only
     */
    public function updateCommissionRate(int $agentId, UpdateCommissionRateRequest $request): JsonResponse
    {
        $command = new UpdateCommissionRateCommand(
            agentId: $agentId,
            commissionRate: $request->getCommissionRate()
        );

        $result = $this->updateCommissionUseCase->execute($command);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors,
                statusCode: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->successResponse(
            data: $result->data,
            message: $result->message
        );
    }

    /**
     * Update sharing rate only
     */
    public function updateSharingRate(int $agentId, UpdateSharingRateRequest $request): JsonResponse
    {
        $command = new UpdateSharingRateCommand(
            agentId: $agentId,
            sharingRate: $request->getSharingRate()
        );

        $result = $this->updateSharingUseCase->execute($command);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors,
                statusCode: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->successResponse(
            data: $result->data,
            message: $result->message
        );
    }

    /**
     * Get appropriate HTTP status code based on error message
     */
    private function getStatusCodeFromMessage(string $message): int
    {
        if (str_contains($message, 'not found')) {
            return 404;
        }

        if (str_contains($message, 'already exist')) {
            return 409;
        }

        if (str_contains($message, 'exceed') || str_contains($message, 'invalid')) {
            return 422;
        }

        if (str_contains($message, 'permission') || str_contains($message, 'unauthorized')) {
            return 403;
        }

        return 400;
    }
}
