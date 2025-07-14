<?php

declare(strict_types=1);
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Shared\Exceptions\ValidationException;

test('creates valid company username', function (): void {
    $username = new Username('A');

    expect($username->value())->toEqual('A');
    expect($username->getAgentTypeFromLength()->value())->toEqual(AgentType::COMPANY);
    expect($username->isValidForAgentType(new AgentType(AgentType::COMPANY)))->toBeTrue();
});
test('creates valid super senior username', function (): void {
    $username = new Username('AB');

    expect($username->value())->toEqual('AB');
    expect($username->getAgentTypeFromLength()->value())->toEqual(AgentType::SUPER_SENIOR);
    expect($username->isValidForAgentType(new AgentType(AgentType::SUPER_SENIOR)))->toBeTrue();
});
test('creates valid member username', function (): void {
    $username = new Username('ABCDABCD123');

    expect($username->value())->toEqual('ABCDABCD123');
    expect($username->getAgentTypeFromLength()->value())->toEqual(AgentType::MEMBER);
    expect($username->isValidForAgentType(new AgentType(AgentType::MEMBER)))->toBeTrue();
});
test('converts to uppercase', function (): void {
    $username = new Username('abcd');

    expect($username->value())->toEqual('ABCD');
});
test('throws exception for empty username', function (): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Username cannot be empty');

    new Username('');
});
test('throws exception for invalid characters', function (): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Username can only contain letters and numbers');

    new Username('ABC@DEF');
});
test('throws exception for invalid length', function (): void {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid username length. Must be 1, 2, 4, 6, 8, or 11 characters');

    new Username('ABC');
    // 3 characters is invalid
});
test('gets correct parent username', function (): void {
    // Super Senior -> Company
    $superSenior = new Username('AB');
    expect($superSenior->getParentUsername())->toEqual('A');

    // Senior -> Super Senior
    $senior = new Username('ABCD');
    expect($senior->getParentUsername())->toEqual('AB');

    // Master -> Senior
    $master = new Username('ABCDEF');
    expect($master->getParentUsername())->toEqual('ABCD');

    // Agent -> Master
    $agent = new Username('ABCDEFGH');
    expect($agent->getParentUsername())->toEqual('ABCDEF');

    // Member -> Agent
    $member = new Username('ABCDEFGH123');
    expect($member->getParentUsername())->toEqual('ABCDEFGH');

    // Company has no parent
    $company = new Username('A');
    expect($company->getParentUsername())->toBeNull();
});
test('validates child relationship', function (): void {
    $company = new Username('A');
    $superSenior = new Username('AB');
    $senior = new Username('ABCD');
    $invalidSenior = new Username('CDEF');

    expect($superSenior->isChildOf($company))->toBeTrue();
    expect($senior->isChildOf($superSenior))->toBeTrue();
    expect($invalidSenior->isChildOf($superSenior))->toBeFalse();
    expect($company->isChildOf($superSenior))->toBeFalse();
    // Company has no parent
});
test('validates username for agent type', function (): void {
    $companyUsername = new Username('A');
    $memberUsername = new Username('ABCDEFGH123');
    $invalidMemberUsername = new Username('ABCDEFGH');

    // Should be 11 chars for member
    expect($companyUsername->isValidForAgentType(new AgentType(AgentType::COMPANY)))->toBeTrue();
    expect($companyUsername->isValidForAgentType(new AgentType(AgentType::MEMBER)))->toBeFalse();

    expect($memberUsername->isValidForAgentType(new AgentType(AgentType::MEMBER)))->toBeTrue();
    expect($memberUsername->isValidForAgentType(new AgentType(AgentType::COMPANY)))->toBeFalse();

    expect($invalidMemberUsername->isValidForAgentType(new AgentType(AgentType::MEMBER)))->toBeFalse();
});
test('generates next username', function (): void {
    $company = new Username('A');
    $agentType = new AgentType(AgentType::SUPER_SENIOR);

    $nextUsername = Username::generateNextUsername($agentType, $company);

    expect($nextUsername->value())->toEqual('AA');
    expect($nextUsername->isChildOf($company))->toBeTrue();
});
test('member username pattern', function (): void {
    $validMember = new Username('ABCDEFGH000');
    $invalidMember1 = new Username('ABCDEFGHABC');

    // Letters instead of numbers
    expect($validMember->isValidForAgentType(new AgentType(AgentType::MEMBER)))->toBeTrue();
    expect($invalidMember1->isValidForAgentType(new AgentType(AgentType::MEMBER)))->toBeFalse();
});
