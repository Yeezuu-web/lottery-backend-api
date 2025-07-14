<?php

namespace App\Domain\Agent\Exceptions;

use App\Shared\Exceptions\DomainException;

class AgentException extends DomainException
{
    public static function notFound(int $agentId): self
    {
        return new self("Agent with ID {$agentId} not found");
    }

    public static function notFoundByUsername(string $username): self
    {
        return new self("Agent with username '{$username}' not found");
    }

    public static function usernameAlreadyExists(string $username): self
    {
        return new self("Username '{$username}' already exists");
    }

    public static function emailAlreadyExists(string $email): self
    {
        return new self("Email '{$email}' already exists");
    }

    public static function cannotCreateAgentType(string $creatorUsername, string $targetType): self
    {
        return new self("Agent '{$creatorUsername}' cannot create agent of type '{$targetType}'");
    }

    public static function cannotManageAgent(string $managerUsername, string $targetUsername): self
    {
        return new self("Agent '{$managerUsername}' cannot manage agent '{$targetUsername}'");
    }

    public static function invalidHierarchy(string $username, string $uplineUsername): self
    {
        return new self("Agent '{$username}' is not a valid child of '{$uplineUsername}'");
    }

    public static function companyCannotHaveUpline(): self
    {
        return new self('Company agents cannot have upline');
    }

    public static function nonCompanyMustHaveUpline(): self
    {
        return new self('Non-company agents must have upline');
    }

    public static function invalidUsernameFormat(string $username, string $agentType): self
    {
        return new self("Username '{$username}' is not valid for agent type '{$agentType}'");
    }

    public static function cannotDrillDown(string $viewerUsername, string $targetUsername): self
    {
        return new self("Agent '{$viewerUsername}' cannot drill down to agent '{$targetUsername}'");
    }

    public static function uplineNotFound(int $uplineId): self
    {
        return new self("Upline agent with ID {$uplineId} not found");
    }

    public static function agentInactive(string $username): self
    {
        return new self("Agent '{$username}' is inactive");
    }

    public static function hierarchyInconsistent(string $username, string $expectedUpline): self
    {
        return new self("Agent '{$username}' hierarchy is inconsistent with expected upline '{$expectedUpline}'");
    }

    public static function invalidUsernameForAgentType(string $username, string $agentType): self
    {
        return new self("Username '{$username}' is not valid for agent type '{$agentType}'");
    }
}
