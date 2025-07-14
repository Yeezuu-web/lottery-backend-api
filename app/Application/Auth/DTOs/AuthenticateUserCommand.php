<?php

namespace App\Application\Auth\DTOs;

final class AuthenticateUserCommand
{
    public readonly string $username;

    public readonly string $password;

    public readonly string $audience;

    public function __construct(string $username, string $password, string $audience)
    {
        $this->username = trim($username);
        $this->password = $password;
        $this->audience = $audience;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'audience' => $this->audience,
        ];
    }
}
