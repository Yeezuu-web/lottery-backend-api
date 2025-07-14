<?php

namespace App\Http\Requests\AgentSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization logic would be handled by middleware
    }

    public function rules(): array
    {
        return [
            'payout_profile' => 'nullable|array',
            'payout_profile.game_2d' => 'nullable|numeric|min:0|max:1000',
            'payout_profile.game_3d' => 'nullable|numeric|min:0|max:1000',
            'payout_profile.game_4d' => 'nullable|numeric|min:0|max:1000',
            'payout_profile.max_commission_sharing_rate' => 'nullable|numeric|min:0|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'sharing_rate' => 'nullable|numeric|min:0|max:100',
            'betting_limits' => 'nullable|array',
            'betting_limits.*.game_type' => 'required_with:betting_limits|string',
            'betting_limits.*.min_bet' => 'required_with:betting_limits|numeric|min:0',
            'betting_limits.*.max_bet' => 'required_with:betting_limits|numeric|min:0',
            'blocked_numbers' => 'nullable|array',
            'blocked_numbers.*' => 'string',
            'auto_settlement' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'commission_rate.numeric' => 'Commission rate must be a number',
            'commission_rate.min' => 'Commission rate must be at least 0',
            'commission_rate.max' => 'Commission rate cannot exceed 100',
            'sharing_rate.numeric' => 'Sharing rate must be a number',
            'sharing_rate.min' => 'Sharing rate must be at least 0',
            'sharing_rate.max' => 'Sharing rate cannot exceed 100',
            'payout_profile.array' => 'Payout profile must be an array',
            'betting_limits.array' => 'Betting limits must be an array',
            'blocked_numbers.array' => 'Blocked numbers must be an array',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for nullable fields
        $this->merge([
            'commission_rate' => $this->commission_rate === '' ? null : $this->commission_rate,
            'sharing_rate' => $this->sharing_rate === '' ? null : $this->sharing_rate,
            'payout_profile' => $this->payout_profile === '' ? null : $this->payout_profile,
            'betting_limits' => $this->betting_limits === '' ? null : $this->betting_limits,
            'blocked_numbers' => $this->blocked_numbers === '' ? null : $this->blocked_numbers,
            'auto_settlement' => $this->auto_settlement === '' ? null : $this->auto_settlement,
            'is_active' => $this->is_active === '' ? null : $this->is_active,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: if both commission and sharing rates are provided,
            // ensure they don't exceed reasonable limits when combined
            $commissionRate = $this->getCommissionRate();
            $sharingRate = $this->getSharingRate();

            if ($commissionRate !== null && $sharingRate !== null) {
                $total = $commissionRate + $sharingRate;
                if ($total > 50) { // Default max combined rate
                    $validator->errors()->add('commission_rate', 'Combined commission and sharing rates cannot exceed 50%');
                }
            }

            // Validate payout profile structure if provided
            if ($this->getPayoutProfile()) {
                $profile = $this->getPayoutProfile();
                $maxRate = $profile['max_commission_sharing_rate'] ?? 50;
                $total = ($commissionRate ?? 0) + ($sharingRate ?? 0);

                if ($total > $maxRate) {
                    $validator->errors()->add('commission_rate', "Combined rates cannot exceed the payout profile maximum of {$maxRate}%");
                }
            }
        });
    }

    public function getPayoutProfile(): ?array
    {
        return $this->input('payout_profile');
    }

    public function getCommissionRate(): ?float
    {
        $rate = $this->input('commission_rate');

        return $rate !== null ? (float) $rate : null;
    }

    public function getSharingRate(): ?float
    {
        $rate = $this->input('sharing_rate');

        return $rate !== null ? (float) $rate : null;
    }

    public function getBettingLimits(): ?array
    {
        return $this->input('betting_limits');
    }

    public function getBlockedNumbers(): ?array
    {
        return $this->input('blocked_numbers');
    }

    public function getAutoSettlement(): ?bool
    {
        $value = $this->input('auto_settlement');

        return $value !== null ? $this->boolean('auto_settlement') : null;
    }

    public function getIsActive(): ?bool
    {
        $value = $this->input('is_active');

        return $value !== null ? $this->boolean('is_active') : null;
    }
}
