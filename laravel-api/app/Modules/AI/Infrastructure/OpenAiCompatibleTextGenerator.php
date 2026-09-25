<?php

namespace App\Modules\AI\Infrastructure;

use App\Modules\AI\Infrastructure\Models\AiGeneration;
use App\Modules\AI\Domain\Contracts\AiTextGeneratorInterface;
use App\Modules\AI\Domain\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

final class OpenAiCompatibleTextGenerator implements AiTextGeneratorInterface
{
    public function json(string $kind, array $messages, array $schema): array
    {
        $model = (string) config('services.ai.model');
        $key = (string) config('services.ai.key');
        $url = rtrim((string) config('services.ai.base_url'), '/');
        if ($key === '' || $url === '' || $model === '') {
            throw new AiProviderException('AI provider is not configured.');
        }$input = ['model' => $model, 'messages' => $messages, 'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => str_replace('-', '_', $kind), 'strict' => true, 'schema' => $schema]]];
        $actor = auth()->user();
        $log = AiGeneration::query()->create(['kind' => $kind, 'model' => $model, 'user_id' => $actor?->id, 'input' => $input, 'status' => 'pending']);
        try {
            $response = Http::timeout((int) config('services.ai.timeout', 30))->withToken($key)->acceptJson()->post($url.'/chat/completions', $input);
            if (! $response->successful()) {
                throw new AiProviderException('AI provider request failed.');
            }$content = $response->json('choices.0.message.content');
            $data = is_string($content) ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : $content;
            if (! is_array($data)) {
                throw new AiProviderException('AI provider returned invalid JSON.');
            }$usage = $response->json('usage', []);
            $log->update(['output' => $data, 'status' => 'completed', 'prompt_tokens' => $usage['prompt_tokens'] ?? null, 'completion_tokens' => $usage['completion_tokens'] ?? null]);

            return $data;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            if ($e instanceof AiProviderException) {
                throw $e;
            }throw new AiProviderException('AI generation failed.');
        }
    }
}
