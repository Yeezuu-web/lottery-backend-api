<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RefreshTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'refresh_token' => [
                'sometimes',
                'required_without:cookie',
                'string',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'refresh_token.required_without' => 'Refresh token is required',
            'refresh_token.string' => 'Refresh token must be a string',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422)
        );
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Check if refresh token is provided either in body or cookie
            $refreshToken = $this->input('refresh_token');
            $cookieToken = $this->cookie('upline_refresh_token') ?? $this->cookie('member_refresh_token');

            if (! $refreshToken && ! $cookieToken) {
                $validator->errors()->add('refresh_token', 'Refresh token must be provided either in request body or cookie');
            }
        });
    }
}
