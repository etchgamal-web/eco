<?php

namespace App\Modules\AI\Domain\Exceptions;

final class AiAllProvidersFailedException extends AiProviderException
{
    public function __construct(string $message = 'Ai All Providers Failed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'AI_ALL_PROVIDERS_FAILED', 503, true, $previous);
    }
}
