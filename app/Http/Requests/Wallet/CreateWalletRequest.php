<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Domain\Wallet\ValueObjects\WalletType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'owner_id' => 'required|integer|min:1',
            'wallet_type' => [
                'required',
                'string',
                Rule::in(array_map(fn ($type) => $type->value, WalletType::cases())),
            ],
            'currency' => 'sometimes|string|size:3|in:USD,KHR,EUR,GBP',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_id.required' => 'Owner ID is required',
            'owner_id.integer' => 'Owner ID must be an integer',
            'owner_id.min' => 'Owner ID must be at least 1',
            'wallet_type.required' => 'Wallet type is required',
            'wallet_type.in' => 'Invalid wallet type. Must be one of: '.implode(', ', array_map(fn ($type) => $type->value, WalletType::cases())),
            'currency.size' => 'Currency must be exactly 3 characters',
            'currency.in' => 'Currency must be one of: USD, KHR, EUR, GBP',
            'is_active.boolean' => 'Is active must be a boolean',
        ];
    }

    public function getOwnerId(): int
    {
        return $this->input('owner_id');
    }

    public function getWalletType(): WalletType
    {
        return WalletType::from($this->input('wallet_type'));
    }

    public function getCurrency(): string
    {
        return $this->input('currency', 'KHR');
    }

    public function getIsActive(): bool
    {
        return $this->boolean('is_active', true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => $this->currency ?? 'USD',
            'is_active' => $this->is_active ?? true,
        ]);
    }
}
