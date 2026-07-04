<?php

namespace App\Exceptions;

use Exception;

class AiGenerationException extends Exception
{
    public static function fromResponse(int $status, string $body): self
    {
        return new self("AI provider request failed with status {$status}: ".str($body)->limit(300));
    }
}
