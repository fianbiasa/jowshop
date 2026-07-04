<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateMetaCapiSettingRequest;
use App\Models\MetaCapiSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MetaCapiSettingController extends Controller
{
    /**
     * Show the Meta Conversions API settings page.
     */
    public function edit(): Response
    {
        $setting = MetaCapiSetting::query()->first();

        return Inertia::render('settings/meta-capi', [
            'metaCapiSetting' => $setting ? [
                'pixel_id' => $setting->pixel_id,
                'test_event_code' => $setting->test_event_code,
                'is_active' => $setting->is_active,
                'is_configured' => true,
            ] : null,
        ]);
    }

    /**
     * Store or update the Meta Conversions API credentials.
     */
    public function update(UpdateMetaCapiSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $setting = MetaCapiSetting::query()->first();

        if ($setting !== null) {
            $setting->update($validated);
        } else {
            MetaCapiSetting::query()->create($validated);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meta Conversions API settings saved.')]);

        return to_route('meta-capi-settings.edit');
    }
}
