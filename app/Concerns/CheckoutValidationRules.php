<?php

namespace App\Concerns;

use App\Models\Funnel;
use Illuminate\Contracts\Validation\ValidationRule;

trait CheckoutValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function checkoutRules(Funnel $funnel): array
    {
        $physical = $funnel->product->isPhysical();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\+?62|0)[\s-]?8(?:[\s-]?\d){7,12}$/'],
            'province' => [$physical ? 'required' : 'nullable', 'string', 'max:255'],
            'city' => [$physical ? 'required' : 'nullable', 'string', 'max:255'],
            'district' => [$physical ? 'required' : 'nullable', 'string', 'max:255'],
            'postal_code' => [$physical ? 'required' : 'nullable', 'string', 'max:10'],
            'destination_area_id' => [$physical ? 'required' : 'nullable', 'string', 'max:255'],
            'destination_label' => [$physical ? 'required' : 'nullable', 'string', 'max:255'],
            'address_line' => [$physical ? 'required' : 'nullable', 'string', 'max:2000'],
        ];
    }
}
