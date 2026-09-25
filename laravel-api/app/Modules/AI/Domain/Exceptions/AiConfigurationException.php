<?php

namespace App\Modules\AI\Domain\Exceptions;

final class AiConfigurationException extends AiProviderException
{
    public function __construct(string $message = 'Ai Configuration Error.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'AI_CONFIGURATION_ERROR', 503, false, $previous);
    }
}
