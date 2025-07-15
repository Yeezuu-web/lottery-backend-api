<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

use Illuminate\Http\Request;

final readonly class AuthenticateUserCommand
{
    public string $username;

    public function __construct(
        string $username,
        public string $password,
        public string $audience,
        public ?Request $request = null
    ) {
        $this->username = mb_trim($username);
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'audience' => $this->audience,
            'has_request_context' => $this->request !== null,
        ];
    }
}
