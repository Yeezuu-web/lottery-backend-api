<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Agent\Models\Agent;

interface AuthenticationDomainServiceInterface
{
    /**
     * Validate if the provided audience is valid
     */
    public function validateAudience(string $audience): void;

    /**
     * Validate agent can authenticate for specific audience
     */
    public function validateAuthentication(Agent $agent, string $audience): void;
}
