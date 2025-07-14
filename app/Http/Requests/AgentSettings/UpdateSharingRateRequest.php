<?php

declare(strict_types=1);

namespace App\Http\Requests\AgentSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSharingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization logic would be handled by middleware
    }

    public function rules(): array
    {
        return [
            'sharing_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'sharing_rate.numeric' => 'Sharing rate must be a number',
            'sharing_rate.min' => 'Sharing rate must be at least 0',
            'sharing_rate.max' => 'Sharing rate cannot exceed 100',
        ];
    }

    public function getSharingRate(): ?float
    {
        $rate = $this->input('sharing_rate');

        return $rate !== null ? (float) $rate : null;
    }

    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for nullable fields
        $this->merge([
            'sharing_rate' => $this->sharing_rate === '' ? null : $this->sharing_rate,
        ]);
    }
}
