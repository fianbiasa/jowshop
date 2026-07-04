<?php

namespace App\Concerns;

use App\Enums\FunnelStatus;
use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait FunnelValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function funnelRules(?int $funnelId = null): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                $funnelId === null
                    ? Rule::unique(Funnel::class)
                    : Rule::unique(Funnel::class)->ignore($funnelId),
            ],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function funnelSettingsRules(?int $funnelId = null): array
    {
        return [
            ...$this->funnelRules($funnelId),
            'status' => ['required', Rule::enum(FunnelStatus::class)],
            'thank_you_message' => ['nullable', 'string'],
            'fb_pixel_id' => ['nullable', 'string', 'max:255'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:255'],
            'ga4_id' => ['nullable', 'string', 'max:255'],
            'google_ads_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
