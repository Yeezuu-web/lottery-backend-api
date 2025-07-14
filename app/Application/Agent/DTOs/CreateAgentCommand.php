<?php

declare(strict_types=1);

namespace App\Application\Agent\DTOs;

use App\Domain\Agent\ValueObjects\AgentType;

final readonly class CreateAgentCommand
{
    public string $phone;

    public string $address;

    public string $city;

    public string $state;

    public string $zip;

    public string $country;

    public string $status;

    public function __construct(public string $username, public string $password, public string $email, public AgentType $agentType, public string $firstName, public string $lastName)
    {
        $this->phone = $phone;
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
        $this->country = $country;
        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
            'email' => $this->email,
            'agent_type' => $this->agentType,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'status' => $this->status,
        ];
    }
}
