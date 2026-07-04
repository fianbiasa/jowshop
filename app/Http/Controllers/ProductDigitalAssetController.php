<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductDigitalAssetRequest;
use App\Models\Product;
use App\Models\ProductDigitalAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductDigitalAssetController extends Controller
{
    /**
     * Store a newly uploaded digital asset for the product.
     */
    public function store(StoreProductDigitalAssetRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store("digital-assets/{$product->id}", 'local')
            : null;

        $product->digitalAssets()->create([
            'file_path' => $filePath,
            'external_url' => $validated['external_url'] ?? null,
            'license_type' => $validated['license_type'],
            'max_downloads' => $validated['max_downloads'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Digital asset uploaded.')]);

        return to_route('products.edit', $product);
    }

    /**
     * Remove a digital asset from the product.
     */
    public function destroy(Product $product, ProductDigitalAsset $digitalAsset): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_if($digitalAsset->product_id !== $product->id, 404);

        if ($digitalAsset->file_path !== null) {
            Storage::disk('local')->delete($digitalAsset->file_path);
        }

        $digitalAsset->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Digital asset removed.')]);

        return to_route('products.edit', $product);
    }
}
