<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait BrandingSettingValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function brandingSettingRules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['boolean'],
        ];
    }
}
