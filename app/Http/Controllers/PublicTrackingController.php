<?php

namespace App\Http\Controllers;

use App\Enums\ShippingProvider;
use App\Exceptions\ShippingRateException;
use App\Models\ShippingSetting;
use App\Services\ShippingRateClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicTrackingController extends Controller
{
    /**
     * Show the public "Cek Resi" tool.
     */
    public function create(): Response
    {
        return Inertia::render('public/tracking-check');
    }

    /**
     * Look up a courier resi's live delivery status. Unlike the admin-only
     * OrderShipmentController::track(), error messages here must stay
     * generic — this is an anonymous public endpoint, so raw provider
     * response bodies are never shown to the visitor.
     */
    public function store(Request $request, ShippingRateClient $client): Response
    {
        $validated = $request->validate([
            'courier' => ['required', 'string', 'max:50'],
            'tracking_number' => ['required', 'string', 'max:100'],
        ]);

        $settings = ShippingSetting::query()->where('is_active', true)->first();

        if ($settings === null || $settings->provider !== ShippingProvider::Biteship) {
            return Inertia::render('public/tracking-check', [
                'error' => 'Fitur cek resi belum tersedia saat ini.',
            ]);
        }

        try {
            $tracking = $client->trackShipment($settings, $validated['courier'], $validated['tracking_number']);
        } catch (ShippingRateException) {
            return Inertia::render('public/tracking-check', [
                'error' => 'Nomor resi tidak ditemukan atau kombinasi kurir salah.',
            ]);
        }

        return Inertia::render('public/tracking-check', [
            'tracking' => $tracking,
        ]);
    }
}
