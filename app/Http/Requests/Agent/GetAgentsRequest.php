<?php

declare(strict_types=1);

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetAgentsRequest extends FormRequest
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
            'viewer_id' => [
                'nullable',
                'integer',
                'exists:agents,id',
            ],
            'target_agent_id' => [
                'nullable',
                'integer',
                'exists:agents,id',
            ],
            'agent_type' => [
                'nullable',
                'string',
                Rule::in(['company', 'super_senior', 'senior', 'master', 'agent', 'member']),
            ],
            'direct_only' => [
                'nullable',
                'boolean',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'viewer_id.exists' => 'Viewer agent not found.',
            'target_agent_id.exists' => 'Target agent not found.',
            'agent_type.in' => 'Invalid agent type.',
            'page.min' => 'Page must be at least 1.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
        ];
    }

    /**
     * Get the viewer ID from the request.
     */
    public function getViewerId(): int
    {
        // Check for explicit viewer_id parameter first
        if ($this->has('viewer_id')) {
            return $this->input('viewer_id');
        }

        // Get from JWT token stored by UplineAuthMiddleware
        $agentId = $this->attributes->get('agent_id');
        if ($agentId) {
            return $agentId;
        }

        // Fallback to Laravel auth (for other auth methods)
        return auth()->id() ?? 0;
    }

    /**
     * Get the target agent ID from the request.
     */
    public function getTargetAgentId(): ?int
    {
        return $this->input('target_agent_id');
    }

    /**
     * Get the agent type filter from the request.
     */
    public function getAgentType(): ?string
    {
        return $this->input('agent_type');
    }

    /**
     * Check if only direct downlines should be returned.
     */
    public function isDirectOnly(): bool
    {
        return $this->input('direct_only', true);
    }

    /**
     * Get the page number from the request.
     */
    public function getPage(): int
    {
        return $this->input('page', 1);
    }

    /**
     * Get the per page limit from the request.
     */
    public function getPerPage(): int
    {
        return $this->input('per_page', 20);
    }
}
