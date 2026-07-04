<?php

use App\Http\Controllers\Settings\AiProviderSettingController;
use App\Http\Controllers\Settings\MetaCapiSettingController;
use App\Http\Controllers\Settings\PaymentSettingController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\ShippingSettingController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/ai-providers', [AiProviderSettingController::class, 'index'])->name('ai-providers.index');
    Route::post('settings/ai-providers', [AiProviderSettingController::class, 'store'])->name('ai-providers.store');
    Route::delete('settings/ai-providers/{aiProviderSetting}', [AiProviderSettingController::class, 'destroy'])->name('ai-providers.destroy');

    Route::get('settings/payment', [PaymentSettingController::class, 'edit'])->name('payment-settings.edit');
    Route::put('settings/payment', [PaymentSettingController::class, 'update'])->name('payment-settings.update');

    Route::get('settings/shipping', [ShippingSettingController::class, 'edit'])->name('shipping-settings.edit');
    Route::put('settings/shipping', [ShippingSettingController::class, 'update'])->name('shipping-settings.update');

    Route::get('settings/meta-capi', [MetaCapiSettingController::class, 'edit'])->name('meta-capi-settings.edit');
    Route::put('settings/meta-capi', [MetaCapiSettingController::class, 'update'])->name('meta-capi-settings.update');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
