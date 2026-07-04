<?php

namespace App\Concerns;

use App\Enums\AiProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AiProviderSettingValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function aiProviderSettingRules(): array
    {
        return [
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'label' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:1000'],
            'default_model' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
