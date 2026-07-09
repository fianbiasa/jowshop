<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWhatsAppSettingRequest;
use App\Models\WhatsAppSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppSettingController extends Controller
{
    /**
     * Show the WhatsApp notification settings page.
     */
    public function edit(): Response
    {
        $setting = WhatsAppSetting::query()->first();

        return Inertia::render('settings/whatsapp', [
            'whatsAppSetting' => $setting ? [
                'is_active' => $setting->is_active,
                'is_configured' => true,
            ] : null,
        ]);
    }

    /**
     * Store or update the Starsender API credentials.
     */
    public function update(UpdateWhatsAppSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $setting = WhatsAppSetting::query()->first();

        if ($setting !== null) {
            $setting->update($validated);
        } else {
            WhatsAppSetting::query()->create($validated);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('WhatsApp notification settings saved.')]);

        return to_route('whatsapp-settings.edit');
    }
}
