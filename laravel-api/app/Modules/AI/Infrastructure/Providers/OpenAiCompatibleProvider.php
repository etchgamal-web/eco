<?php

namespace App\Modules\AI\Infrastructure\Providers;

use App\Modules\AI\Domain\Contracts\AiProviderInterface;
use App\Modules\AI\Domain\Exceptions\AiProviderException;
use App\Modules\AI\Domain\Exceptions\AiConfigurationException;
use App\Modules\AI\Domain\Exceptions\AiResponseException;
use App\Modules\AI\Domain\ValueObjects\AiRequest;
use App\Modules\AI\Domain\ValueObjects\AiResponse;
use Illuminate\Support\Facades\Http;

abstract class OpenAiCompatibleProvider implements AiProviderInterface
{
    abstract protected function configKey(): string;

    public function name(): string
    {
        return $this->configKey();
    }

    public function generate(AiRequest $request): AiResponse
    {
        $c = (array) config('services.ai.providers.'.$this->configKey());
        $key = (string) ($c['key'] ?? '');
        $url = rtrim((string) ($c['base_url'] ?? ''), '/');
        $model = (string) ($request->metadata['model'] ?? $c['model'] ?? '');
        if ($key === '' || $url === '' || $model === '') {
            throw new AiConfigurationException('AI provider configuration is incomplete.');
        }$payload = ['model' => $model, 'messages' => $request->messages, 'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => str_replace('-', '_', $request->kind), 'strict' => true, 'schema' => $request->schema]]];
        $res = Http::timeout((int) config('services.ai.timeout', 30))->withToken($key)->acceptJson()->post($url.'/chat/completions', $payload);
        if (! $res->successful()) {
            throw new AiProviderException($this->configKey().' request failed.');
        }$content = $res->json('choices.0.message.content');
        $data = is_string($content) ? json_decode($content, true) : $content;
        if (! is_array($data)) {
            throw new AiResponseException('AI provider returned an invalid structured response.');
        }$u = $res->json('usage', []);

        return new AiResponse($data, $this->configKey(), $model, (int) ($u['prompt_tokens'] ?? 0), (int) ($u['completion_tokens'] ?? 0));
    }
}
