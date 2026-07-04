<?php

namespace App\Http\Controllers;

use App\Enums\FunnelEventType;
use App\Enums\FunnelSessionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendMetaConversionEvent;
use App\Models\FunnelSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Services\DigitalDeliveryService;
use App\Services\DuitkuGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DuitkuWebhookController extends Controller
{
    /**
     * Handle Duitku's server-to-server payment callback.
     */
    public function handle(Request $request, DuitkuGateway $gateway, DigitalDeliveryService $digitalDeliveries): Response|RedirectResponse
    {
        $payload = $request->all();

        $settings = PaymentSetting::query()->where('is_active', true)->first();

        if ($settings === null || ! $gateway->verifyCallbackSignature($settings, $payload)) {
            Log::warning('Duitku webhook signature verification failed.', ['payload' => $payload]);

            return response('Invalid signature', 403);
        }

        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');
        $payment = Payment::query()->where('merchant_order_id', $merchantOrderId)->first();

        if ($payment === null) {
            return response('Order not found', 404);
        }

        // Idempotent: once a payment reaches a final state, ignore repeat callbacks.
        if ($payment->status !== PaymentStatus::Pending) {
            return response('OK');
        }

        $isPaid = (string) ($payload['resultCode'] ?? '') === '00';

        $payment->update([
            'status' => $isPaid ? PaymentStatus::Paid : PaymentStatus::Failed,
            'gateway_reference' => $payload['reference'] ?? null,
            'payment_method' => $payload['paymentCode'] ?? null,
            'paid_at' => $isPaid ? now() : null,
            'raw_callback' => $payload,
        ]);

        $order = $payment->order;

        if ($isPaid) {
            $order->update(['status' => OrderStatus::Paid]);
            $this->decrementPhysicalStock($order);
            $digitalDeliveries->generateForOrder($order);
        }

        $funnelSession = FunnelSession::query()->where('order_id', $order->id)->first();

        if ($funnelSession !== null) {
            $event = $funnelSession->recordEvent(
                $isPaid ? FunnelEventType::PaymentSuccess : FunnelEventType::PaymentFailed,
            );

            SendMetaConversionEvent::dispatch($event->id)->afterResponse();

            if ($isPaid) {
                $funnelSession->update([
                    'status' => FunnelSessionStatus::Converted,
                    'completed_at' => now(),
                ]);
            }
        }

        return response('OK');
    }

    /**
     * Decrement stock for physical items that haven't been decremented yet.
     * Safe to call on every successful payment for an order (main payment,
     * plus any later incremental upsell/downsell payments) since each item
     * is only ever decremented once, tracked via `stock_decremented_at`.
     */
    private function decrementPhysicalStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if ($item->stock_decremented_at !== null) {
                continue;
            }

            $product = $item->product;

            if ($product->isPhysical() && $product->stock !== null) {
                $product->decrement('stock', min($item->quantity, $product->stock));
            }

            $item->update(['stock_decremented_at' => now()]);
        }
    }
}
