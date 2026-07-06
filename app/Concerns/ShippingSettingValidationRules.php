<?php

namespace App\Concerns;

use App\Enums\ShippingProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ShippingSettingValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function shippingSettingRules(): array
    {
        return [
            'provider' => ['required', Rule::enum(ShippingProvider::class)],
            'api_key' => ['required', 'string', 'max:255'],
            'origin_area_id' => ['required', 'string', 'max:255'],
            'origin_label' => ['nullable', 'string', 'max:255'],
            'origin_contact_name' => ['nullable', 'string', 'max:255', 'required_if:auto_book_shipping,1'],
            'origin_contact_phone' => ['nullable', 'string', 'max:50', 'required_if:auto_book_shipping,1'],
            'origin_address' => ['nullable', 'string', 'max:500', 'required_if:auto_book_shipping,1'],
            'origin_postal_code' => ['nullable', 'string', 'max:10', 'required_if:auto_book_shipping,1'],
            'enabled_couriers' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'auto_book_shipping' => ['boolean'],
        ];
    }
}
