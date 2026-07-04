<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait MetaCapiSettingValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function metaCapiSettingRules(): array
    {
        return [
            'pixel_id' => ['required', 'string', 'max:255'],
            'access_token' => ['required', 'string', 'max:2048'],
            'test_event_code' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
