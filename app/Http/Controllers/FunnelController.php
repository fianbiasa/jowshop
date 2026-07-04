<?php

namespace App\Http\Controllers;

use App\Enums\FunnelStatus;
use App\Http\Requests\StoreFunnelRequest;
use App\Http\Requests\UpdateFunnelRequest;
use App\Models\Funnel;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FunnelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Funnel::class);

        $funnels = Funnel::query()
            ->with('product')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('funnels/index', [
            'funnels' => $funnels,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Funnel::class);

        return Inertia::render('funnels/create', [
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFunnelRequest $request): RedirectResponse
    {
        $funnel = $request->user()->funnels()->create([
            ...$request->validated(),
            'status' => FunnelStatus::Draft,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Funnel created.')]);

        return to_route('funnels.edit', $funnel);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Funnel $funnel): Response
    {
        $this->authorize('update', $funnel);

        $funnel->load(['product', 'salespage', 'offers']);

        return Inertia::render('funnels/edit', [
            'funnel' => [
                ...$funnel->toArray(),
                'thank_you_message' => $funnel->thank_you_content['message'] ?? null,
                'fb_pixel_id' => $funnel->pixel_settings['fb_pixel_id'] ?? null,
                'tiktok_pixel_id' => $funnel->pixel_settings['tiktok_pixel_id'] ?? null,
                'ga4_id' => $funnel->pixel_settings['ga4_id'] ?? null,
                'google_ads_id' => $funnel->pixel_settings['google_ads_id'] ?? null,
            ],
            'offers' => $funnel->offers,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'type']),
            'publishRequirementErrors' => $funnel->publishRequirementErrors(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFunnelRequest $request, Funnel $funnel): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['status'] === FunnelStatus::Published->value && ! $funnel->isPublished()) {
            $errors = $funnel->publishRequirementErrors();

            if ($errors !== []) {
                throw ValidationException::withMessages(['status' => $errors]);
            }
        }

        $funnel->update([
            'product_id' => $validated['product_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'status' => $validated['status'],
            'thank_you_content' => ($validated['thank_you_message'] ?? null) !== null
                ? ['message' => $validated['thank_you_message']]
                : null,
            'pixel_settings' => array_filter([
                'fb_pixel_id' => $validated['fb_pixel_id'] ?? null,
                'tiktok_pixel_id' => $validated['tiktok_pixel_id'] ?? null,
                'ga4_id' => $validated['ga4_id'] ?? null,
                'google_ads_id' => $validated['google_ads_id'] ?? null,
            ]) ?: null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Funnel updated.')]);

        return to_route('funnels.edit', $funnel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Funnel $funnel): RedirectResponse
    {
        $this->authorize('delete', $funnel);

        $funnel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Funnel deleted.')]);

        return to_route('funnels.index');
    }
}
