<?php

declare(strict_types=1);

namespace App\Http\Controllers\AgentSettings;

use App\Application\AgentSettings\Commands\CreateAgentSettingsCommand;
use App\Application\AgentSettings\Commands\UpdateAgentSettingsCommand;
use App\Application\AgentSettings\Queries\GetAgentSettingsQuery;
use App\Application\AgentSettings\UseCases\CreateAgentSettingsUseCase;
use App\Application\AgentSettings\UseCases\GetAgentSettingsUseCase;
use App\Application\AgentSettings\UseCases\UpdateAgentSettingsUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgentSettings\CreateAgentSettingsRequest;
use App\Http\Requests\AgentSettings\UpdateAgentSettingsRequest;
use App\Traits\HttpApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgentSettingsController extends Controller
{
    use HttpApiResponse;

    public function __construct(
        private readonly CreateAgentSettingsUseCase $createUseCase,
        private readonly UpdateAgentSettingsUseCase $updateUseCase,
        private readonly GetAgentSettingsUseCase $getUseCase
    ) {}

    /**
     * Get agent settings
     */
    public function show(int $agentId, Request $request): JsonResponse
    {
        $includeUsage = $request->boolean('include_usage', true);

        $query = new GetAgentSettingsQuery(
            agentId: $agentId,
            includeUsage: $includeUsage
        );

        $result = $this->getUseCase->execute($query);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors ?? [],
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
            dailyLimit: $request->getDailyLimit(),
            maxCommission: $request->getMaxCommission(),
            maxShare: $request->getMaxShare(),
            numberLimits: $request->getNumberLimits(),
            blockedNumbers: $request->getBlockedNumbers()
        );

        $result = $this->createUseCase->execute($command);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors ?? [],
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
            dailyLimit: $request->getDailyLimit(),
            maxCommission: $request->getMaxCommission(),
            maxShare: $request->getMaxShare(),
            numberLimits: $request->getNumberLimits(),
            blockedNumbers: $request->getBlockedNumbers()
        );

        $result = $this->updateUseCase->execute($command);

        if (! $result->success) {
            return $this->errorResponse(
                message: $result->message,
                errors: $result->errors ?? [],
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

        if (str_contains($message, 'exceed') || str_contains($message, 'invalid') || str_contains($message, 'limit')) {
            return 422;
        }

        if (str_contains($message, 'blocked')) {
            return 422;
        }

        if (str_contains($message, 'permission') || str_contains($message, 'unauthorized')) {
            return 403;
        }

        return 400;
    }
}
