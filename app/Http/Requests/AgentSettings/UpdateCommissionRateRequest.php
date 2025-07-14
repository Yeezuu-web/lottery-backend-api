<?php

namespace App\Http\Requests\AgentSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommissionRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization logic would be handled by middleware
    }

    public function rules(): array
    {
        return [
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'commission_rate.numeric' => 'Commission rate must be a number',
            'commission_rate.min' => 'Commission rate must be at least 0',
            'commission_rate.max' => 'Commission rate cannot exceed 100',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for nullable fields
        $this->merge([
            'commission_rate' => $this->commission_rate === '' ? null : $this->commission_rate,
        ]);
    }

    public function getCommissionRate(): ?float
    {
        $rate = $this->input('commission_rate');

        return $rate !== null ? (float) $rate : null;
    }
}
