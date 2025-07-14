<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Domain\Wallet\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;

final class TransferFundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'from_wallet_id' => 'required|integer|min:1|exists:agent_multi_wallets,id',
            'to_wallet_id' => 'required|integer|min:1|exists:agent_multi_wallets,id|different:from_wallet_id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'sometimes|string|size:3|in:USD,KHR,EUR,GBP',
            'reference' => 'sometimes|string|max:255|unique:wallet_transactions,reference',
            'description' => 'required|string|max:1000',
            'metadata' => 'sometimes|array',
            'order_id' => 'sometimes|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'from_wallet_id.required' => 'Source wallet ID is required',
            'from_wallet_id.exists' => 'Source wallet does not exist',
            'to_wallet_id.required' => 'Destination wallet ID is required',
            'to_wallet_id.exists' => 'Destination wallet does not exist',
            'to_wallet_id.different' => 'Destination wallet must be different from source wallet',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be at least 0.01',
            'amount.max' => 'Amount cannot exceed 999,999.99',
            'currency.size' => 'Currency must be exactly 3 characters',
            'currency.in' => 'Currency must be one of: USD, KHR, EUR, GBP',
            'reference.unique' => 'Reference must be unique',
            'description.required' => 'Description is required',
            'description.max' => 'Description cannot exceed 1000 characters',
            'metadata.array' => 'Metadata must be an array',
            'order_id.integer' => 'Order ID must be an integer',
            'order_id.min' => 'Order ID must be at least 1',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            // Additional validation logic
            $amount = $this->input('amount');
            if ($amount && $amount > 50000) {
                $validator->errors()->add('amount', 'Large transfers require additional approval');
            }
        });
    }

    public function getFromWalletId(): int
    {
        return $this->input('from_wallet_id');
    }

    public function getToWalletId(): int
    {
        return $this->input('to_wallet_id');
    }

    public function getAmount(): Money
    {
        return Money::fromFloat(
            $this->input('amount'),
            $this->input('currency', 'KHR')
        );
    }

    public function getReference(): string
    {
        return $this->input('reference');
    }

    public function getDescription(): string
    {
        return $this->input('description');
    }

    public function getMetadata(): ?array
    {
        return $this->input('metadata');
    }

    public function getOrderId(): ?int
    {
        return $this->input('order_id');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => $this->currency ?? 'KHR',
            'reference' => $this->reference ?? $this->generateReference(),
        ]);
    }

    private function generateReference(): string
    {
        return 'TRF_'.time().'_'.mt_rand(100000, 999999);
    }
}
