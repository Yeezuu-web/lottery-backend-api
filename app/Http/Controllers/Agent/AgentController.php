<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agent;

use App\Application\Agent\Commands\CreateAgentCommand;
use App\Application\Agent\Commands\GetAgentsCommand;
use App\Application\Agent\UseCases\CreateAgentUseCase;
use App\Application\Agent\UseCases\GetAgentsUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\CreateAgentRequest;
use App\Http\Requests\Agent\GetAgentsRequest;
use App\Traits\HasAuthorization;
use App\Traits\HttpApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AgentController extends Controller
{
    use HasAuthorization;
    use HttpApiResponse;
    public function __construct(
        private readonly CreateAgentUseCase $createAgentUseCase,
        private readonly GetAgentsUseCase $getAgentsUseCase
    ) {}

    /**
     * Get list of agents (direct downlines or drill-down)
     *
     * Authorization: This route is protected by 'authorize:manage_agents' middleware
     * which automatically checks if the user has the 'manage_agents' permission
     */
    public function index(GetAgentsRequest $request): JsonResponse
    {
        // Additional authorization check can be done here if needed
        // $this->checkPermission('manage_agents');
        // $this->checkAgentManagement($targetAgentId);
        $command = new GetAgentsCommand(
            viewerId: $request->getViewerId(),
            targetAgentId: $request->getTargetAgentId(),
            agentType: $request->getAgentType(),
            directOnly: $request->isDirectOnly(),
            page: $request->getPage(),
            perPage: $request->getPerPage()
        );

        $response = $this->getAgentsUseCase->execute($command);

        return $this->success(
            $response->toArray(),
            'Agents retrieved successfully'
        );
    }

    /**
     * Create new agent
     */
    public function store(CreateAgentRequest $request): JsonResponse
    {
        $command = new CreateAgentCommand(
            username: $request->getUsername(),
            agentType: $request->getAgentType(),
            creatorId: $request->getCreatorId(),
            name: $request->getName(),
            email: $request->getEmail(),
            password: $request->getPassword(),
            uplineId: $request->getUplineId()
        );

        $response = $this->createAgentUseCase->execute($command);

        return $this->success(
            $response->toArray(),
            'Agent created successfully'
        );
    }

    /**
     * Get agent details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // For now, we'll use the GetAgentsUseCase to get a single agent
        // In a real implementation, you might want a separate GetAgentUseCase
        $viewerId = $this->getAuthenticatedAgentId($request);

        $command = new GetAgentsCommand(
            viewerId: $viewerId,
            targetAgentId: $id,
            directOnly: false
        );

        $response = $this->getAgentsUseCase->execute($command);
        $agents = $response->getAgents();

        if ($agents === []) {
            return $this->notFound('Agent not found');
        }

        return $this->success(
            $agents[0]->toArray(),
            'Agent retrieved successfully'
        );
    }

    /**
     * Get agent's direct downlines (for drill-down navigation)
     */
    public function downlines(Request $request, int $id): JsonResponse
    {
        $viewerId = $this->getAuthenticatedAgentId($request);

        $command = new GetAgentsCommand(
            viewerId: $viewerId,
            targetAgentId: $id,
            directOnly: true
        );

        $response = $this->getAgentsUseCase->execute($command);

        return $this->success(
            $response->toArray(),
            'Agent downlines retrieved successfully'
        );
    }

    /**
     * Get agent hierarchy tree
     */
    public function hierarchy(Request $request, int $id): JsonResponse
    {
        $viewerId = $this->getAuthenticatedAgentId($request);

        $command = new GetAgentsCommand(
            viewerId: $viewerId,
            targetAgentId: $id,
            directOnly: false
        );

        $response = $this->getAgentsUseCase->execute($command);

        return $this->success(
            $response->toArray(),
            'Agent hierarchy retrieved successfully'
        );
    }

    /**
     * Get agent types that can be created
     */
    public function creatableTypes(Request $request): JsonResponse
    {
        $this->getAuthenticatedAgentId($request);

        // This would typically be handled by a separate use case
        // For now, return static agent types
        $agentTypes = [
            [
                'value' => 'company',
                'display' => 'Company',
                'username_length' => 1,
                'username_pattern' => 'A-Z',
            ],
            [
                'value' => 'super_senior',
                'display' => 'Super Senior',
                'username_length' => 2,
                'username_pattern' => 'AA-ZZ',
            ],
            [
                'value' => 'senior',
                'display' => 'Senior',
                'username_length' => 4,
                'username_pattern' => 'AAAA-ZZZZ',
            ],
            [
                'value' => 'master',
                'display' => 'Master',
                'username_length' => 6,
                'username_pattern' => 'AAAAAA-ZZZZZZ',
            ],
            [
                'value' => 'agent',
                'display' => 'Agent',
                'username_length' => 8,
                'username_pattern' => 'AAAAAAAA-ZZZZZZZZ',
            ],
            [
                'value' => 'member',
                'display' => 'Member',
                'username_length' => 11,
                'username_pattern' => 'AAAAAAAA000-ZZZZZZZZ999',
            ],
        ];

        return $this->success(
            $agentTypes,
            'Agent types retrieved successfully'
        );
    }

    /**
     * Get the authenticated agent ID from request
     */
    private function getAuthenticatedAgentId(Request $request): int
    {
        // Check for explicit viewer_id parameter first
        if ($request->has('viewer_id')) {
            return $request->input('viewer_id');
        }

        // Get from JWT token stored by UplineAuthMiddleware
        $agentId = $request->attributes->get('agent_id');
        if ($agentId) {
            return $agentId;
        }

        return Auth::user()?->id ?? 0;
    }
}
