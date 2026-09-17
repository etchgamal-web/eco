<?php

namespace App\Modules\AI\Infrastructure\Providers;

final class OpenAiProvider extends OpenAiCompatibleProvider
{
    protected function configKey(): string
    {
        return 'openai';
    }
}
