<?php

namespace App\Http\Controllers;

use App\Models\OrderItemDelivery;
use App\Models\ProductDigitalAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadController extends Controller
{
    /**
     * Show the digital access page for a delivered order item.
     */
    public function show(string $token): Response
    {
        $delivery = OrderItemDelivery::query()
            ->where('download_token', $token)
            ->with('orderItem.product.digitalAssets', 'orderItem.order')
            ->firstOrFail();

        $product = $delivery->orderItem->product;

        return Inertia::render('public/digital-download', [
            'download_token' => $delivery->download_token,
            'order_number' => $delivery->orderItem->order->order_number,
            'product_name' => $product->name,
            'license_key' => $delivery->license_key,
            'download_count' => $delivery->download_count,
            'max_downloads' => $delivery->max_downloads,
            'expires_at' => $delivery->expires_at,
            'is_expired' => $delivery->isExpired(),
            'has_reached_limit' => $delivery->hasReachedDownloadLimit(),
            'assets' => $product->digitalAssets->map(fn (ProductDigitalAsset $asset) => [
                'id' => $asset->id,
                'name' => $asset->file_path !== null
                    ? basename($asset->file_path)
                    : $asset->external_url,
            ]),
        ]);
    }

    /**
     * Stream (or redirect to) the actual file, enforcing the delivery's
     * download limit and expiry.
     */
    public function download(string $token, ProductDigitalAsset $asset): StreamedResponse|RedirectResponse
    {
        $delivery = OrderItemDelivery::query()
            ->where('download_token', $token)
            ->firstOrFail();

        abort_if($delivery->isExpired(), 403, 'Tautan unduhan telah kedaluwarsa.');
        abort_if($delivery->hasReachedDownloadLimit(), 403, 'Batas unduhan telah tercapai.');
        abort_if($asset->product_id !== $delivery->orderItem->product_id, 404);

        $delivery->increment('download_count');

        if ($asset->external_url !== null) {
            return redirect()->away($asset->external_url);
        }

        abort_if($asset->file_path === null, 404);

        return Storage::disk('local')->download($asset->file_path);
    }
}
