<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFunnelOfferRequest;
use App\Http\Requests\UpdateFunnelOfferRequest;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FunnelOfferController extends Controller
{
    /**
     * Store a newly created offer for the given funnel.
     */
    public function store(StoreFunnelOfferRequest $request, Funnel $funnel): RedirectResponse
    {
        $funnel->offers()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer added.')]);

        return to_route('funnels.edit', $funnel);
    }

    /**
     * Update the specified offer.
     */
    public function update(UpdateFunnelOfferRequest $request, Funnel $funnel, FunnelOffer $offer): RedirectResponse
    {
        $offer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer updated.')]);

        return to_route('funnels.edit', $funnel);
    }

    /**
     * Remove the specified offer (and its children, via cascade delete).
     */
    public function destroy(Funnel $funnel, FunnelOffer $offer): RedirectResponse
    {
        $this->authorize('update', $funnel);

        $offer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer deleted.')]);

        return to_route('funnels.edit', $funnel);
    }
}
