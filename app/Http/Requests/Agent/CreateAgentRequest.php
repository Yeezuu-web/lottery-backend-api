<?php

declare(strict_types=1);

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateAgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'agent_type' => [
                'required',
                'string',
                Rule::in(['company', 'super senior', 'senior', 'master', 'agent', 'member']),
            ],
            'username' => [
                'required',
                'string',
                'regex:/^[A-Z0-9]+$/',
                'unique:agents,username',
                function ($attribute, $value, $fail): void {
                    // Custom validation for username length based on agent type
                    $agentType = $this->input('agent_type');
                    if (! $agentType) {
                        $fail('Agent type is required to validate username.');

                        return;
                    }

                    $expectedLength = $this->getExpectedUsernameLength($agentType);

                    if (mb_strlen($value) !== $expectedLength) {
                        $fail(sprintf('Username must be exactly %d characters for %s type.', $expectedLength, $agentType));
                    }

                    // Additional pattern validation
                    if (! $this->isValidUsernamePattern($value, $agentType)) {
                        $fail(sprintf('Username pattern is invalid for %s type.', $agentType));
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:agents,email',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'upline_id' => [
                'nullable',
                'integer',
                'exists:agents,id',
                function ($attribute, $value, $fail): void {
                    $agentType = $this->input('agent_type');

                    // Company agents should not have upline
                    if ($agentType === 'company' && $value !== null) {
                        $fail('Company agents cannot have upline.');
                    }

                    // Non-company agents should have upline (unless provided via creator_id)
                    if ($agentType !== 'company' && $value === null && $this->getCreatorId() === 0) {
                        $fail('Non-company agents must have upline.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.unique' => 'Username already exists.',
            'username.regex' => 'Username can only contain uppercase letters and numbers.',
            'agent_type.required' => 'Agent type is required.',
            'agent_type.in' => 'Invalid agent type.',
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Invalid email format.',
            'email.unique' => 'Email already exists.',
            'password.min' => 'Password must be at least 8 characters.',
            'upline_id.exists' => 'Upline agent not found.',
        ];
    }

    /**
     * Get the username from the request.
     */
    public function getUsername(): string
    {
        return $this->input('username');
    }

    /**
     * Get the agent type from the request.
     */
    public function getAgentType(): string
    {
        return $this->input('agent_type');
    }

    /**
     * Get the creator ID from the request.
     */
    public function getCreatorId(): int
    {
        $agentId = $this->attributes->get('agent_id');
        if ($agentId) {
            return $agentId;
        }

        // Fallback to Laravel auth (for other auth methods)
        return auth()->id() ?? 0;
    }

    /**
     * Get the name from the request.
     */
    public function getName(): string
    {
        return $this->input('name');
    }

    /**
     * Get the email from the request.
     */
    public function getEmail(): string
    {
        return $this->input('email');
    }

    /**
     * Get the password from the request.
     */
    public function getPassword(): ?string
    {
        return $this->input('password');
    }

    /**
     * Get the upline ID from the request.
     */
    public function getUplineId(): ?int
    {
        return $this->input('upline_id');
    }

    /**
     * Get expected username length for agent type.
     */
    private function getExpectedUsernameLength(string $agentType): int
    {
        return match ($agentType) {
            'company' => 1,
            'super senior' => 2,
            'senior' => 4,
            'master' => 6,
            'agent' => 8,
            'member' => 11,
            default => 8
        };
    }

    /**
     * Validate username pattern for agent type.
     */
    private function isValidUsernamePattern(string $username, string $agentType): bool
    {
        return match ($agentType) {
            'company' => (bool) preg_match('/^[A-Z]$/', $username),
            'super senior' => (bool) preg_match('/^[A-Z]{2}$/', $username),
            'senior' => (bool) preg_match('/^[A-Z]{4}$/', $username),
            'master' => (bool) preg_match('/^[A-Z]{6}$/', $username),
            'agent' => (bool) preg_match('/^[A-Z]{8}$/', $username),
            'member' => (bool) preg_match('/^[A-Z]{8}\d{3}$/', $username),
            default => false
        };
    }
}
