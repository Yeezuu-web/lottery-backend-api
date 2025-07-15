<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'period' => [
                'required',
                'string',
                Rule::in(['evening', 'night']),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['2D', '3D']),
            ],
            'channels' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'channels.*' => [
                'required',
                'string',
                'max:10',
            ],
            'option' => [
                'required',
                'string',
                Rule::in(['none', 'X', '\\', '>', '\\|', '>|']),
            ],
            'number' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                function ($attribute, $value, $fail): void {
                    $type = $this->input('type');
                    if ($type === '2D' && mb_strlen($value) !== 2) {
                        $fail('The number must be 2 digits for 2D betting.');
                    } elseif ($type === '3D' && mb_strlen($value) !== 3) {
                        $fail('The number must be 3 digits for 3D betting.');
                    }
                },
            ],
            'amount' => [
                'required',
                'numeric',
                'min:100',
                'max:10000000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'period.required' => 'The betting period is required.',
            'period.in' => 'The betting period must be either evening or night.',
            'type.required' => 'The betting type is required.',
            'type.in' => 'The betting type must be either 2D or 3D.',
            'channels.required' => 'At least one channel must be selected.',
            'channels.min' => 'At least one channel must be selected.',
            'channels.max' => 'Maximum 10 channels can be selected.',
            'channels.*.required' => 'Each channel must be specified.',
            'channels.*.string' => 'Each channel must be a valid string.',
            'option.required' => 'The betting option is required.',
            'option.in' => 'The betting option must be one of: none, X, \\, >, \\|, >|.',
            'number.required' => 'The betting number is required.',
            'number.regex' => 'The betting number must contain only digits.',
            'amount.required' => 'The betting amount is required.',
            'amount.numeric' => 'The betting amount must be a number.',
            'amount.min' => 'The minimum betting amount is 1,000 KHR.',
            'amount.max' => 'The maximum betting amount is 1,000,000 KHR.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'period' => 'betting period',
            'type' => 'betting type',
            'channels' => 'channels',
            'option' => 'betting option',
            'number' => 'betting number',
            'amount' => 'betting amount',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
