<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateShippingSettingRequest;
use App\Models\ShippingSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShippingSettingController extends Controller
{
    /**
     * Show the shipping (RajaOngkir/Komerce) settings page.
     */
    public function edit(): Response
    {
        $setting = ShippingSetting::query()->first();

        return Inertia::render('settings/shipping', [
            'shippingSetting' => $setting ? [
                'provider' => $setting->provider,
                'origin_area_id' => $setting->origin_area_id,
                'origin_label' => $setting->origin_label,
                'enabled_couriers' => implode(',', $setting->enabled_couriers ?? []),
                'is_active' => $setting->is_active,
                'is_configured' => true,
            ] : null,
        ]);
    }

    /**
     * Store or update the shipping provider credentials.
     */
    public function update(UpdateShippingSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $couriers = array_values(array_filter(array_map(
            'trim',
            explode(',', $validated['enabled_couriers'] ?? ''),
        )));

        $data = [
            'provider' => $validated['provider'],
            'api_key' => $validated['api_key'],
            'origin_area_id' => $validated['origin_area_id'],
            'origin_label' => $validated['origin_label'] ?? null,
            'enabled_couriers' => $couriers === [] ? null : $couriers,
            'is_active' => $validated['is_active'] ?? false,
        ];

        $setting = ShippingSetting::query()->first();

        if ($setting !== null) {
            $setting->update($data);
        } else {
            ShippingSetting::query()->create($data);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shipping settings saved.')]);

        return to_route('shipping-settings.edit');
    }
}
