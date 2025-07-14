<?php

namespace App\Http\Controllers\Agent;

use App\Application\Agent\Commands\UpdateAgentCommand;
use App\Application\Agent\UseCases\UpdateAgentUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\UpdateAgentRequest;
use App\Traits\HttpApiResponse;
use Illuminate\Http\JsonResponse;

class UpdateAgentController extends Controller
{
    use HttpApiResponse;

    public function __construct(
        private readonly UpdateAgentUseCase $updateAgentUseCase
    ) {}

    public function __invoke(UpdateAgentRequest $request, int $id): JsonResponse
    {
        $command = new UpdateAgentCommand(
            id: $id,
            name: $request->getName(),
            email: $request->getEmail(),
            password: $request->getPassword(),
            updatorId: $request->getUpdatorId(),
        );

        $response = $this->updateAgentUseCase->execute($command);

        return $this->success(
            $response->toArray(),
            'Agent updated successfully'
        );
    }
}
