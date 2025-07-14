<?php

namespace App\Application\Agent\DTOs;

use App\Domain\Agent\ValueObjects\AgentType;

final class CreateAgentCommand
{
    public readonly string $username;

    public readonly string $password;

    public readonly string $email;

    public readonly AgentType $agentType;

    public readonly string $firstName;

    public readonly string $lastName;

    public readonly string $phone;

    public readonly string $address;

    public readonly string $city;

    public readonly string $state;

    public readonly string $zip;

    public readonly string $country;

    public readonly string $status;

    public function __construct(string $username, string $password, string $email, AgentType $agentType, string $firstName, string $lastName)
    {
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->agentType = $agentType;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
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
