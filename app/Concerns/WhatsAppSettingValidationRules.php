<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait WhatsAppSettingValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function whatsAppSettingRules(): array
    {
        return [
            'api_key' => ['required', 'string', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }
}
