<?php

namespace App\Modules\AI\Infrastructure\Providers;

final class GroqProvider extends OpenAiCompatibleProvider
{
    protected function configKey(): string
    {
        return 'groq';
    }
}
