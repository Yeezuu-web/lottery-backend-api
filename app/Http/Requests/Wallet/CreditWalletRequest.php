<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreditWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        $creditTypes = array_map(
            fn ($type) => $type->value,
            array_filter(TransactionType::cases(), fn ($type): bool => $type->isCredit())
        );

        return [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'sometimes|string|size:3|in:USD,KHR,EUR,GBP',
            'transaction_type' => [
                'required',
                'string',
                Rule::in($creditTypes),
            ],
            'reference' => 'sometimes|string|max:255|unique:wallet_transactions,reference',
            'description' => 'required|string|max:1000',
            'metadata' => 'sometimes|array',
            'order_id' => 'sometimes|integer|min:1',
            'related_transaction_id' => 'sometimes|integer|min:1|exists:wallet_transactions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be at least 0.01',
            'amount.max' => 'Amount cannot exceed 999,999.99',
            'currency.size' => 'Currency must be exactly 3 characters',
            'currency.in' => 'Currency must be one of: USD, KHR, EUR, GBP',
            'transaction_type.required' => 'Transaction type is required',
            'transaction_type.in' => 'Invalid transaction type for credit operation',
            'reference.required' => 'Reference is required',
            'reference.unique' => 'Reference must be unique',
            'description.required' => 'Description is required',
            'description.max' => 'Description cannot exceed 1000 characters',
            'metadata.array' => 'Metadata must be an array',
            'order_id.integer' => 'Order ID must be an integer',
            'order_id.min' => 'Order ID must be at least 1',
            'related_transaction_id.integer' => 'Related transaction ID must be an integer',
            'related_transaction_id.exists' => 'Related transaction does not exist',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            // Additional validation logic if needed
            $amount = $this->input('amount');
            if ($amount && $amount > 100000) {
                $validator->errors()->add('amount', 'Large transactions require additional approval');
            }
        });
    }

    public function getAmount(): Money
    {
        return Money::fromAmount(
            (float) $this->input('amount'),
            $this->input('currency', 'KHR')
        );
    }

    public function getTransactionType(): TransactionType
    {
        return TransactionType::from($this->input('transaction_type'));
    }

    public function getReference(): ?string
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

    public function getRelatedTransactionId(): ?int
    {
        return $this->input('related_transaction_id');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => $this->currency ?? 'KHR',
        ]);
    }


}
