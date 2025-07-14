<?php

declare(strict_types=1);

namespace App\Domain\Agent\Exceptions;

use App\Shared\Exceptions\DomainException;

final class AgentException extends DomainException
{
    public static function notFound(int $agentId): self
    {
        return new self(sprintf('Agent with ID %d not found', $agentId));
    }

    public static function notFoundByUsername(string $username): self
    {
        return new self(sprintf("Agent with username '%s' not found", $username));
    }

    public static function usernameAlreadyExists(string $username): self
    {
        return new self(sprintf("Username '%s' already exists", $username));
    }

    public static function emailAlreadyExists(string $email): self
    {
        return new self(sprintf("Email '%s' already exists", $email));
    }

    public static function cannotCreateAgentType(string $creatorUsername, string $targetType): self
    {
        return new self(sprintf("Agent '%s' cannot create agent of type '%s'", $creatorUsername, $targetType));
    }

    public static function cannotManageAgent(string $managerUsername, string $targetUsername): self
    {
        return new self(sprintf("Agent '%s' cannot manage agent '%s'", $managerUsername, $targetUsername));
    }

    public static function invalidHierarchy(string $username, string $uplineUsername): self
    {
        return new self(sprintf("Agent '%s' is not a valid child of '%s'", $username, $uplineUsername));
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
        return new self(sprintf("Username '%s' is not valid for agent type '%s'", $username, $agentType));
    }

    public static function cannotDrillDown(string $viewerUsername, string $targetUsername): self
    {
        return new self(sprintf("Agent '%s' cannot drill down to agent '%s'", $viewerUsername, $targetUsername));
    }

    public static function uplineNotFound(int $uplineId): self
    {
        return new self(sprintf('Upline agent with ID %d not found', $uplineId));
    }

    public static function agentInactive(string $username): self
    {
        return new self(sprintf("Agent '%s' is inactive", $username));
    }

    public static function hierarchyInconsistent(string $username, string $expectedUpline): self
    {
        return new self(sprintf("Agent '%s' hierarchy is inconsistent with expected upline '%s'", $username, $expectedUpline));
    }

    public static function invalidUsernameForAgentType(string $username, string $agentType): self
    {
        return new self(sprintf("Username '%s' is not valid for agent type '%s'", $username, $agentType));
    }
}
