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
            'address' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
