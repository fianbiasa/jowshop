<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\AiGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreAiProviderSettingRequest;
use App\Models\AiProviderSetting;
use App\Services\AiProviderClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AiProviderSettingController extends Controller
{
    /**
     * Display the AI provider settings page.
     */
    public function index(): Response
    {
        return Inertia::render('settings/ai-providers', [
            'aiProviderSettings' => AiProviderSetting::query()->latest()->get(),
        ]);
    }

    /**
     * Store a newly created AI provider setting, after verifying the
     * credentials actually work with a small test prompt.
     */
    public function store(StoreAiProviderSettingRequest $request, AiProviderClient $client): RedirectResponse
    {
        $validated = $request->validated();

        $testSetting = new AiProviderSetting([
            'provider' => $validated['provider'],
            'api_key' => $validated['api_key'],
            'default_model' => $validated['default_model'],
        ]);

        try {
            $client->generate($testSetting, 'Balas dengan kata "OK" saja untuk menguji koneksi ini.');
        } catch (AiGenerationException $e) {
            throw ValidationException::withMessages([
                'api_key' => "Tidak bisa terhubung ke provider AI. Periksa API key dan model default. ({$e->getMessage()})",
            ]);
        }

        if ($validated['is_default'] ?? false) {
            AiProviderSetting::query()->update(['is_default' => false]);
        }

        $request->user()->aiProviderSettings()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI provider added.')]);

        return to_route('ai-providers.index');
    }

    /**
     * Remove the specified AI provider setting.
     */
    public function destroy(AiProviderSetting $aiProviderSetting): RedirectResponse
    {
        $aiProviderSetting->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI provider removed.')]);

        return to_route('ai-providers.index');
    }
}
