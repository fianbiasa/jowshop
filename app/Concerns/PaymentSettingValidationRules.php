<?php

namespace App\Concerns;

use App\Enums\PaymentEnvironment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PaymentSettingValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function paymentSettingRules(): array
    {
        return [
            'merchant_code' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:255'],
            'environment' => ['required', Rule::enum(PaymentEnvironment::class)],
            'is_active' => ['boolean'],
        ];
    }
}
