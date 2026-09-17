<?php

namespace App\Modules\AI\Infrastructure\Providers;

final class OpenRouterProvider extends OpenAiCompatibleProvider
{
    protected function configKey(): string
    {
        return 'openrouter';
    }
}
