<?php

namespace Tests\Unit\Domain\Agent\ValueObjects;

use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Shared\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class UsernameTest extends TestCase
{
    public function test_creates_valid_company_username(): void
    {
        $username = new Username('A');

        $this->assertEquals('A', $username->value());
        $this->assertEquals(AgentType::COMPANY, $username->getAgentTypeFromLength()->value());
        $this->assertTrue($username->isValidForAgentType(new AgentType(AgentType::COMPANY)));
    }

    public function test_creates_valid_super_senior_username(): void
    {
        $username = new Username('AB');

        $this->assertEquals('AB', $username->value());
        $this->assertEquals(AgentType::SUPER_SENIOR, $username->getAgentTypeFromLength()->value());
        $this->assertTrue($username->isValidForAgentType(new AgentType(AgentType::SUPER_SENIOR)));
    }

    public function test_creates_valid_member_username(): void
    {
        $username = new Username('ABCDABCD123');

        $this->assertEquals('ABCDABCD123', $username->value());
        $this->assertEquals(AgentType::MEMBER, $username->getAgentTypeFromLength()->value());
        $this->assertTrue($username->isValidForAgentType(new AgentType(AgentType::MEMBER)));
    }

    public function test_converts_to_uppercase(): void
    {
        $username = new Username('abcd');

        $this->assertEquals('ABCD', $username->value());
    }

    public function test_throws_exception_for_empty_username(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Username cannot be empty');

        new Username('');
    }

    public function test_throws_exception_for_invalid_characters(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Username can only contain letters and numbers');

        new Username('ABC@DEF');
    }

    public function test_throws_exception_for_invalid_length(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid username length. Must be 1, 2, 4, 6, 8, or 11 characters');

        new Username('ABC'); // 3 characters is invalid
    }

    public function test_gets_correct_parent_username(): void
    {
        // Super Senior -> Company
        $superSenior = new Username('AB');
        $this->assertEquals('A', $superSenior->getParentUsername());

        // Senior -> Super Senior
        $senior = new Username('ABCD');
        $this->assertEquals('AB', $senior->getParentUsername());

        // Master -> Senior
        $master = new Username('ABCDEF');
        $this->assertEquals('ABCD', $master->getParentUsername());

        // Agent -> Master
        $agent = new Username('ABCDEFGH');
        $this->assertEquals('ABCDEF', $agent->getParentUsername());

        // Member -> Agent
        $member = new Username('ABCDEFGH123');
        $this->assertEquals('ABCDEFGH', $member->getParentUsername());

        // Company has no parent
        $company = new Username('A');
        $this->assertNull($company->getParentUsername());
    }

    public function test_validates_child_relationship(): void
    {
        $company = new Username('A');
        $superSenior = new Username('AB');
        $senior = new Username('ABCD');
        $invalidSenior = new Username('CDEF');

        $this->assertTrue($superSenior->isChildOf($company));
        $this->assertTrue($senior->isChildOf($superSenior));
        $this->assertFalse($invalidSenior->isChildOf($superSenior));
        $this->assertFalse($company->isChildOf($superSenior)); // Company has no parent
    }

    public function test_validates_username_for_agent_type(): void
    {
        $companyUsername = new Username('A');
        $memberUsername = new Username('ABCDEFGH123');
        $invalidMemberUsername = new Username('ABCDEFGH'); // Should be 11 chars for member

        $this->assertTrue($companyUsername->isValidForAgentType(new AgentType(AgentType::COMPANY)));
        $this->assertFalse($companyUsername->isValidForAgentType(new AgentType(AgentType::MEMBER)));

        $this->assertTrue($memberUsername->isValidForAgentType(new AgentType(AgentType::MEMBER)));
        $this->assertFalse($memberUsername->isValidForAgentType(new AgentType(AgentType::COMPANY)));

        $this->assertFalse($invalidMemberUsername->isValidForAgentType(new AgentType(AgentType::MEMBER)));
    }

    public function test_generates_next_username(): void
    {
        $company = new Username('A');
        $agentType = new AgentType(AgentType::SUPER_SENIOR);

        $nextUsername = Username::generateNextUsername($agentType, $company);

        $this->assertEquals('AA', $nextUsername->value());
        $this->assertTrue($nextUsername->isChildOf($company));
    }

    public function test_member_username_pattern(): void
    {
        $validMember = new Username('ABCDEFGH000');
        $invalidMember1 = new Username('ABCDEFGHABC'); // Letters instead of numbers

        $this->assertTrue($validMember->isValidForAgentType(new AgentType(AgentType::MEMBER)));
        $this->assertFalse($invalidMember1->isValidForAgentType(new AgentType(AgentType::MEMBER)));
    }
}
