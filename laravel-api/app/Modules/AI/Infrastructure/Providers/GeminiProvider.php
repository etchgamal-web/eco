<?php

namespace App\Modules\AI\Infrastructure\Providers;

use App\Modules\AI\Domain\Contracts\AiProviderInterface;
use App\Modules\AI\Domain\Exceptions\AiConfigurationException;
use App\Modules\AI\Domain\Exceptions\AiProviderException;
use App\Modules\AI\Domain\Exceptions\AiResponseException;
use App\Modules\AI\Domain\ValueObjects\AiRequest;
use App\Modules\AI\Domain\ValueObjects\AiResponse;
use Illuminate\Support\Facades\Http;

final class GeminiProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'gemini';
    }

    public function generate(AiRequest $r): AiResponse
    {
        $c = (array) config('services.ai.providers.gemini');
        $key = (string) ($c['key'] ?? '');
        $model = (string) ($r->metadata['model'] ?? $c['model'] ?? '');
        if ($key === '' || $model === '') {
            throw new AiConfigurationException('AI provider configuration is incomplete.');
        }$text = implode("\n", array_map(fn ($m) => strtoupper($m['role']).': '.$m['content'], $r->messages));
        $url = rtrim((string) $c['base_url'], '/').'/models/'.$model.':generateContent?key='.urlencode($key);
        $res = Http::timeout((int) config('services.ai.timeout', 30))->acceptJson()->post($url, ['contents' => [['role' => 'user', 'parts' => [['text' => $text]]]], 'generationConfig' => ['responseMimeType' => 'application/json', 'responseSchema' => $r->schema]]);
        if (! $res->successful()) {
            throw new AiProviderException('gemini request failed.');
        }$text = $res->json('candidates.0.content.parts.0.text');
        $data = is_string($text) ? json_decode($text, true) : null;
        if (! is_array($data)) {
            throw new AiResponseException('AI provider returned an invalid structured response.');
        }$u = $res->json('usageMetadata', []);

        return new AiResponse($data, 'gemini', $model, (int) ($u['promptTokenCount'] ?? 0), (int) ($u['candidatesTokenCount'] ?? 0));
    }
}
