<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItemDelivery;
use App\Notifications\DigitalDeliveryAvailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DigitalDeliveryService
{
    /**
     * Generate a download token (and license key, if applicable) for every
     * digital order item that doesn't have a delivery yet, then email the
     * customer a single summary of everything they can access. Safe to call
     * on every successful payment for an order, since each item is only
     * ever given a delivery once.
     */
    public function generateForOrder(Order $order): void
    {
        $order->loadMissing('items.product.digitalAssets', 'customer');

        /** @var Collection<int, OrderItemDelivery> $deliveries */
        $deliveries = collect();

        foreach ($order->items as $item) {
            if (! $item->product->isDigital()) {
                continue;
            }

            if (OrderItemDelivery::query()->where('order_item_id', $item->id)->exists()) {
                continue;
            }

            $asset = $item->product->digitalAssets->first();

            $delivery = OrderItemDelivery::query()->create([
                'order_item_id' => $item->id,
                'download_token' => Str::random(48),
                'license_key' => $asset?->license_type === 'license_key' ? $this->generateLicenseKey() : null,
                'max_downloads' => $asset?->max_downloads,
                'expires_at' => now()->addDays(30),
                'delivered_at' => now(),
            ]);

            $delivery->setRelation('orderItem', $item);
            $deliveries->push($delivery);
        }

        if ($deliveries->isNotEmpty() && $order->customer !== null) {
            $order->customer->notify(new DigitalDeliveryAvailable($order, $deliveries));
        }
    }

    private function generateLicenseKey(): string
    {
        return collect(range(1, 4))
            ->map(fn () => Str::upper(Str::random(4)))
            ->implode('-');
    }
}
