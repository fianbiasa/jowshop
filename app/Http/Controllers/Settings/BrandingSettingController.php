<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingSettingRequest;
use App\Models\BrandingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingSettingController extends Controller
{
    /**
     * Show the site branding (logo) settings page.
     */
    public function edit(): Response
    {
        $setting = BrandingSetting::query()->first();

        return Inertia::render('settings/branding', [
            'logoUrl' => $setting?->logoUrl(),
        ]);
    }

    /**
     * Upload a new logo, or remove the existing one.
     */
    public function update(UpdateBrandingSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $setting = BrandingSetting::query()->first() ?? new BrandingSetting;

        if ($request->hasFile('logo') || ($validated['remove_logo'] ?? false)) {
            if ($setting->logo_path !== null) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $setting->logo_path = $request->hasFile('logo')
                ? $request->file('logo')->store('branding', 'public')
                : null;

            $setting->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return to_route('branding-settings.edit');
    }
}
