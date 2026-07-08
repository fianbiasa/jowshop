<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait CdnSettingValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function cdnSettingRules(): array
    {
        return [
            'pull_zone_url' => ['nullable', 'required_if:is_active,1', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
