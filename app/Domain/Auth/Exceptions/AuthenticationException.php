<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use Exception;

final class AuthenticationException extends Exception
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid username or password', 401);
    }

    public static function agentNotActive(): self
    {
        return new self('Agent account is not active', 403);
    }

    public static function invalidAudience(string $agentType, string $audience): self
    {
        return new self(sprintf("Agent type '%s' cannot authenticate '%s'", $agentType, $audience), 403);
    }

    public static function tokenExpired(): self
    {
        return new self('Token has expired', 401);
    }

    public static function invalidToken(): self
    {
        return new self('Invalid or malformed token', 401);
    }

    public static function tokenNotFound(): self
    {
        return new self('Token not provided', 401);
    }

    public static function refreshTokenExpired(): self
    {
        return new self('Refresh token has expired', 401);
    }

    public static function invalidRefreshToken(): self
    {
        return new self('Invalid refresh token', 401);
    }

    public static function blocked(): self
    {
        return new self('Account temporarily blocked due to multiple failed login attempts', 429);
    }
}
