<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ShippingRateException extends Exception
{
    public static function fromResponse(int $status, string $body): self
    {
        return new self("Shipping provider request failed with status {$status}: ".str($body)->limit(300));
    }

    public static function fromConnectionFailure(Throwable $previous): self
    {
        return new self("Shipping provider request failed: {$previous->getMessage()}", previous: $previous);
    }
}
