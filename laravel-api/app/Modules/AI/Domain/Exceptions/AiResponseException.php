<?php

namespace App\Modules\AI\Domain\Exceptions;

final class AiResponseException extends AiProviderException
{
    public function __construct(string $message = 'Ai Invalid Response.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'AI_INVALID_RESPONSE', 502, true, $previous);
    }
}
