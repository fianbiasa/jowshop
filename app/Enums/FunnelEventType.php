<?php

namespace App\Enums;

enum FunnelEventType: string
{
    case SalespageView = 'salespage_view';
    case CheckoutView = 'checkout_view';
    case CheckoutSubmitted = 'checkout_submitted';
    case BumpView = 'bump_view';
    case BumpAccepted = 'bump_accepted';
    case BumpDeclined = 'bump_declined';
    case UpsellView = 'upsell_view';
    case UpsellAccepted = 'upsell_accepted';
    case UpsellDeclined = 'upsell_declined';
    case DownsellView = 'downsell_view';
    case DownsellAccepted = 'downsell_accepted';
    case DownsellDeclined = 'downsell_declined';
    case PaymentPending = 'payment_pending';
    case PaymentSuccess = 'payment_success';
    case PaymentFailed = 'payment_failed';
    case ThankyouView = 'thankyou_view';

    /**
     * Map to the equivalent Meta Pixel/Conversions API standard event name,
     * or null if this event type isn't relevant for ad platform tracking.
     */
    public function toMetaStandardEvent(): ?string
    {
        return match ($this) {
            self::SalespageView => 'ViewContent',
            self::CheckoutView => 'InitiateCheckout',
            self::BumpAccepted, self::UpsellAccepted, self::DownsellAccepted => 'AddToCart',
            self::PaymentSuccess => 'Purchase',
            default => null,
        };
    }
}
