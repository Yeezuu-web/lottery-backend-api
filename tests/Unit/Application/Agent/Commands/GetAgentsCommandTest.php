<?php

declare(strict_types=1);
use App\Application\Agent\Commands\GetAgentsCommand;
use App\Domain\Agent\ValueObjects\AgentType;

test('get agent command', function (): void {
    $command = new GetAgentsCommand(
        viewerId: 1,
        targetAgentId: 2,
        agentType: AgentType::COMPANY,
        directOnly: true
    );

    expect($command)->toBeInstanceOf(GetAgentsCommand::class);
    expect($command->getViewerId())->toBe(1);
    expect($command->getTargetAgentId())->toBe(2);
    expect($command->getPage())->toBe(1);
    expect($command->getPerPage())->toBe(20);
    expect($command->isDirectOnly())->toBe(true);
    expect($command->getAgentType())->toBe(AgentType::COMPANY);
    expect($command->toArray()['agent_type'])->toBe(AgentType::COMPANY);
});
