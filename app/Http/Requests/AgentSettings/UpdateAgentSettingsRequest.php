<?php

declare(strict_types=1);

namespace App\Http\Requests\AgentSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAgentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization logic would be handled by middleware
    }

    public function rules(): array
    {
        return [
            'daily_limit' => 'nullable|integer|min:0',
            'max_commission' => 'nullable|numeric|min:0|max:100',
            'max_share' => 'nullable|numeric|min:0|max:100',
            'number_limits' => 'nullable|array',
            'number_limits.*.game_type' => 'required_with:number_limits|string|in:2D,3D',
            'number_limits.*.number' => 'required_with:number_limits|string',
            'number_limits.*.limit' => 'required_with:number_limits|integer|min:0',
            'blocked_numbers' => 'nullable|array',
            'blocked_numbers.*' => 'string',
        ];
    }

    public function messages(): array
    {
        return [
            'daily_limit.integer' => 'Daily limit must be an integer',
            'daily_limit.min' => 'Daily limit must be non-negative',
            'max_commission.numeric' => 'Max commission rate must be numeric',
            'max_commission.min' => 'Max commission rate cannot be negative',
            'max_commission.max' => 'Max commission rate cannot exceed 100%',
            'max_share.numeric' => 'Max share rate must be numeric',
            'max_share.min' => 'Max share rate cannot be negative',
            'max_share.max' => 'Max share rate cannot exceed 100%',
            'number_limits.array' => 'Number limits must be an array',
            'number_limits.*.game_type.required_with' => 'Game type is required for number limits',
            'number_limits.*.game_type.in' => 'Game type must be either 2D or 3D',
            'number_limits.*.number.required_with' => 'Number is required for number limits',
            'number_limits.*.limit.required_with' => 'Limit amount is required for number limits',
            'number_limits.*.limit.integer' => 'Limit amount must be an integer',
            'number_limits.*.limit.min' => 'Limit amount must be non-negative',
            'blocked_numbers.array' => 'Blocked numbers must be an array',
            'blocked_numbers.*.string' => 'Each blocked number must be a string',
        ];
    }

    public function getDailyLimit(): ?int
    {
        return $this->input('daily_limit');
    }

    public function getMaxCommission(): ?float
    {
        $commission = $this->input('max_commission');

        return $commission !== null ? (float) $commission : null;
    }

    public function getMaxShare(): ?float
    {
        $share = $this->input('max_share');

        return $share !== null ? (float) $share : null;
    }

    public function getNumberLimits(): ?array
    {
        $numberLimits = $this->input('number_limits');

        if ($numberLimits === null) {
            return null;
        }

        $result = [];
        foreach ($numberLimits as $limit) {
            $gameType = $limit['game_type'];
            $number = $limit['number'];
            $amount = $limit['limit'];

            if (! isset($result[$gameType])) {
                $result[$gameType] = [];
            }

            $result[$gameType][$number] = $amount;
        }

        return $result;
    }

    public function getBlockedNumbers(): ?array
    {
        return $this->input('blocked_numbers');
    }

    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for nullable fields
        $this->merge([
            'daily_limit' => $this->daily_limit === '' ? null : $this->daily_limit,
            'max_commission' => $this->max_commission === '' ? null : $this->max_commission,
            'max_share' => $this->max_share === '' ? null : $this->max_share,
            'number_limits' => $this->number_limits === '' ? null : $this->number_limits,
            'blocked_numbers' => $this->blocked_numbers === '' ? null : $this->blocked_numbers,
        ]);
    }
}
