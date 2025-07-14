<?php

declare(strict_types=1);

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                'unique:agents,email,'.$this->route('id'),
            ],
            'password' => [
                'sometimes',
                'string',
                'min:8',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string',
            'name.max' => 'Name must be less than 255 characters',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email must be unique',
            'password.string' => 'Password must be a string',
            'password.min' => 'Password must be at least 8 characters',
        ];
    }

    public function getName(): ?string
    {
        return $this->input('name');
    }

    public function getEmail(): ?string
    {
        return $this->input('email');
    }

    public function getPassword(): ?string
    {
        return $this->input('password');
    }

    /**
     * Get the updator ID from the request.
     */
    public function getUpdatorId(): int
    {
        $agentId = $this->attributes->get('agent_id');
        if ($agentId) {
            return $agentId;
        }

        // Fallback to Laravel auth (for other auth methods)
        return auth()->id() ?? 0;
    }
}
