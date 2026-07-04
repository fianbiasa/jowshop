<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePaymentSettingRequest;
use App\Models\PaymentSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingController extends Controller
{
    /**
     * Show the Duitku payment settings page.
     */
    public function edit(): Response
    {
        $setting = PaymentSetting::query()->first();

        return Inertia::render('settings/payment', [
            'paymentSetting' => $setting ? [
                'environment' => $setting->environment,
                'is_active' => $setting->is_active,
                'is_configured' => true,
            ] : null,
        ]);
    }

    /**
     * Store or update the Duitku payment credentials.
     */
    public function update(UpdatePaymentSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $setting = PaymentSetting::query()->first();

        if ($setting !== null) {
            $setting->update($validated);
        } else {
            PaymentSetting::query()->create($validated);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment settings saved.')]);

        return to_route('payment-settings.edit');
    }
}
