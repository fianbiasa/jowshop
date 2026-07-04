<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\PaymentSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DuitkuGateway
{
    /**
     * Create a payment transaction with Duitku and return the hosted
     * payment page details (paymentUrl, reference, etc). The amount and
     * merchant order id are explicit so this can be used both for the
     * initial checkout payment and for later incremental upsell/downsell
     * payments tied to the same order.
     *
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException
     */
    public function createTransaction(
        PaymentSetting $settings,
        Order $order,
        int $amount,
        string $merchantOrderId,
        string $callbackUrl,
        string $returnUrl,
    ): array {
        $order->loadMissing('customer');

        try {
            $response = Http::asJson()->timeout(15)->post("{$settings->baseApiUrl()}/inquiry", [
                'merchantCode' => $settings->merchant_code,
                'paymentAmount' => $amount,
                'merchantOrderId' => $merchantOrderId,
                'productDetails' => "Order {$order->order_number}",
                'email' => $order->customer->email,
                'customerVaName' => $order->customer->name,
                'phoneNumber' => $order->customer->phone,
                'callbackUrl' => $callbackUrl,
                'returnUrl' => $returnUrl,
                'expiryPeriod' => 60,
                'signature' => $this->signInquiry($settings, $merchantOrderId, $amount),
            ]);
        } catch (ConnectionException $exception) {
            throw PaymentGatewayException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw PaymentGatewayException::fromResponse($response->status(), $response->body());
        }

        $data = $response->json();

        if (! is_array($data) || ($data['statusCode'] ?? null) !== '00') {
            throw PaymentGatewayException::fromResponse($response->status(), $response->body());
        }

        return $data;
    }

    /**
     * Verify the signature on an incoming Duitku callback payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyCallbackSignature(PaymentSetting $settings, array $payload): bool
    {
        $signature = $payload['signature'] ?? null;

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $amount = (string) ($payload['amount'] ?? '');
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');

        $expected = md5($settings->merchant_code.$amount.$merchantOrderId.$settings->api_key);

        return hash_equals($expected, $signature);
    }

    public function amountAsInt(string $total): int
    {
        return (int) round((float) $total);
    }

    private function signInquiry(PaymentSetting $settings, string $merchantOrderId, int $amount): string
    {
        return md5($settings->merchant_code.$merchantOrderId.$amount.$settings->api_key);
    }
}
