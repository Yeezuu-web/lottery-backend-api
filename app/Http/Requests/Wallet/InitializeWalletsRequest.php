<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class InitializeWalletsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'owner_id' => 'required|integer|min:1',
            'currency' => 'sometimes|string|size:3|in:USD,KHR,EUR,GBP',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_id.required' => 'Owner ID is required',
            'owner_id.integer' => 'Owner ID must be an integer',
            'owner_id.min' => 'Owner ID must be at least 1',
            'currency.size' => 'Currency must be exactly 3 characters',
            'currency.in' => 'Currency must be one of: USD, KHR, EUR, GBP',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => $this->currency ?? 'KHR',
        ]);
    }

    public function getOwnerId(): int
    {
        return $this->input('owner_id');
    }

    public function getCurrency(): string
    {
        return $this->input('currency', 'KHR');
    }
}
