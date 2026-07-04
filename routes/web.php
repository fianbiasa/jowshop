<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutOfferController;
use App\Http\Controllers\CheckoutShippingController;
use App\Http\Controllers\CheckoutUpsellController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalDownloadController;
use App\Http\Controllers\DuitkuWebhookController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\FunnelOfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderLookupController;
use App\Http\Controllers\OrderShipmentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDigitalAssetController;
use App\Http\Controllers\PublicSalespageController;
use App\Http\Controllers\SalespageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('f/{funnel:slug}')->name('public.')->middleware('throttle:public-funnel')->group(function () {
    Route::get('/', [PublicSalespageController::class, 'show'])->name('salespage.show');

    Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('checkout/bayar', [CheckoutController::class, 'pay'])->name('checkout.pay');
    Route::post('checkout/bayar', [CheckoutController::class, 'payStore'])->name('checkout.pay.store');
    Route::get('checkout/kembali', [CheckoutController::class, 'return'])->name('checkout.return');

    Route::get('checkout/offers/{offer}', [CheckoutOfferController::class, 'show'])->name('checkout.offer.show');
    Route::post('checkout/offers/{offer}', [CheckoutOfferController::class, 'respond'])->name('checkout.offer.respond');

    Route::get('upsell/{offer}', [CheckoutUpsellController::class, 'show'])->name('checkout.upsell.show');
    Route::post('upsell/{offer}', [CheckoutUpsellController::class, 'respond'])->name('checkout.upsell.respond');

    Route::get('checkout/destinations', [CheckoutShippingController::class, 'search'])->name('checkout.destinations.search');
    Route::get('checkout/pengiriman', [CheckoutShippingController::class, 'show'])->name('checkout.shipping.show');
    Route::post('checkout/pengiriman', [CheckoutShippingController::class, 'store'])->name('checkout.shipping.store');
});

Route::post('webhooks/duitku', [DuitkuWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.duitku');

Route::middleware('throttle:public-download')->group(function () {
    Route::get('unduh/{token}', [DigitalDownloadController::class, 'show'])->name('digital-download.show');
    Route::get('unduh/{token}/{asset}', [DigitalDownloadController::class, 'download'])->name('digital-download.download');
});

Route::middleware('throttle:order-lookup')->group(function () {
    Route::get('pesanan-saya', [OrderLookupController::class, 'create'])->name('order-lookup.create');
    Route::post('pesanan-saya', [OrderLookupController::class, 'store'])->name('order-lookup.store');
    Route::get('pesanan-saya/{order:order_number}', [OrderLookupController::class, 'show'])->name('order-lookup.show');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class)->except('show');
    Route::post('products/{product}/digital-assets', [ProductDigitalAssetController::class, 'store'])->name('products.digital-assets.store');
    Route::delete('products/{product}/digital-assets/{digitalAsset}', [ProductDigitalAssetController::class, 'destroy'])->name('products.digital-assets.destroy');

    Route::resource('funnels', FunnelController::class)->except('show');
    Route::resource('funnels.offers', FunnelOfferController::class)
        ->except(['index', 'show', 'create', 'edit']);

    Route::get('funnels/{funnel}/salespage', [SalespageController::class, 'edit'])->name('funnels.salespage.edit');
    Route::put('funnels/{funnel}/salespage', [SalespageController::class, 'update'])->name('funnels.salespage.update');
    Route::post('funnels/{funnel}/salespage/generate', [SalespageController::class, 'generate'])->name('funnels.salespage.generate');

    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::put('orders/{order}/shipment', [OrderShipmentController::class, 'update'])->name('orders.shipment.update');
});

require __DIR__.'/settings.php';
