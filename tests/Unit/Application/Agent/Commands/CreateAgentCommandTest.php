<?php

declare(strict_types=1);
use App\Application\Agent\Commands\CreateAgentCommand;
use App\Domain\Agent\ValueObjects\AgentType;

test('create agent command', function (): void {
    $agent = new CreateAgentCommand(
        username: 'A',
        agentType: AgentType::COMPANY,
        creatorId: 1,
        name: 'Test Agent',
        email: 'test@example.com',
        password: 'password',
        uplineId: null
    );

    expect($agent)->toBeInstanceOf(CreateAgentCommand::class);
    expect($agent->getName())->toBe('Test Agent');
    expect($agent->getEmail())->toBe('test@example.com');
    expect($agent->getPassword())->toBe('password');
    expect($agent->getCreatorId())->toBe(1);
    expect($agent->getUsername())->toBe('A');
    expect($agent->getUplineId())->toBe(null);
    expect($agent->getAgentType())->toBe(AgentType::COMPANY);
    expect($agent->toArray()['username'])->toBe('A');
});
