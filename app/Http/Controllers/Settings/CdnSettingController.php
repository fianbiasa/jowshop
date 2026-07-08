<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCdnSettingRequest;
use App\Models\CdnSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CdnSettingController extends Controller
{
    /**
     * Show the CDN (Bunny Pull Zone) settings page.
     */
    public function edit(): Response
    {
        $setting = CdnSetting::query()->first();

        return Inertia::render('settings/cdn', [
            'cdnSetting' => $setting ? [
                'pull_zone_url' => $setting->pull_zone_url,
                'is_active' => $setting->is_active,
            ] : null,
        ]);
    }

    /**
     * Store or update the CDN settings.
     */
    public function update(UpdateCdnSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $setting = CdnSetting::query()->first();

        if ($setting !== null) {
            $setting->update($validated);
        } else {
            CdnSetting::query()->create($validated);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('CDN settings saved.')]);

        return to_route('cdn-settings.edit');
    }
}
