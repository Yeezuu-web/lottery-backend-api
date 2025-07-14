<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

final readonly class AuthenticateUserCommand
{
    public string $username;

    public function __construct(string $username, public string $password, public string $audience)
    {
        $this->username = mb_trim($username);
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'audience' => $this->audience,
        ];
    }
}
