<?php

namespace App\Modules\AI\Infrastructure;

use App\Models\AiGeneration;
use App\Modules\AI\Domain\Contracts\AiGatewayInterface;
use App\Modules\AI\Domain\Contracts\AiProviderInterface;
use App\Modules\AI\Domain\Contracts\AiTextGeneratorInterface;
use App\Modules\AI\Domain\Exceptions\AiProviderException;
use App\Modules\AI\Domain\Exceptions\AiAllProvidersFailedException;
use App\Modules\AI\Domain\ValueObjects\AiRequest;
use App\Modules\AI\Domain\ValueObjects\AiResponse;
use App\Modules\AI\Infrastructure\Providers\GeminiProvider;
use App\Modules\AI\Infrastructure\Providers\GroqProvider;
use App\Modules\AI\Infrastructure\Providers\OpenAiProvider;
use App\Modules\AI\Infrastructure\Providers\OpenRouterProvider;
use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;

final class AiGateway implements AiGatewayInterface, AiTextGeneratorInterface
{
    /** @var array<string, AiProviderInterface> */
    private array $providers;

    public function __construct(private readonly SettingsRepositoryInterface $settings, GeminiProvider $gemini, OpenAiProvider $openai, GroqProvider $groq, OpenRouterProvider $openrouter)
    {
        $this->providers = ['gemini' => $gemini, 'openai' => $openai, 'groq' => $groq, 'openrouter' => $openrouter];
    }

    public function json(string $kind, array $messages, array $schema): array
    {
        return $this->generate(new AiRequest($kind, $messages, $schema))->toArray();
    }

    public function generate(AiRequest $request): AiResponse
    {
        $settings = $this->settings->getByGroup('ai');
        $values = [];
        foreach ($settings as $setting) {
            $values[$setting->key] = $setting->getTypedValue();
        }
        $order = $values['provider_order'] ?? config('services.ai.provider_order', ['gemini', 'openai', 'groq', 'openrouter']);
        if (! is_array($order)) {
            $order = [$order];
        }
        $primaryModel = $values['primary_model'] ?? config('services.ai.model');
        $fallback = (bool) ($values['fallback_enabled'] ?? true);
        $errors = [];
        foreach (array_values(array_unique($order)) as $index => $providerName) {
            $provider = $this->providers[$providerName] ?? null;
            if (! $provider) {
                continue;
            }try {
                $model = $index === 0 ? $primaryModel : ($values['model_'.$providerName] ?? null);
                $response = $provider->generate(new AiRequest($request->kind, $request->messages, $request->schema, array_merge($request->metadata, ['model' => $model])));
                JsonSchemaValidator::validateOrFail($response->data, $request->schema);
                $this->log($request, $response);

                return $response;
            } catch (\Throwable $e) {
                $errors[$providerName] = $e->getMessage();
                $this->logFailure($request, $providerName, $model, $e);
                if (! $fallback) {
                    if ($e instanceof AiProviderException) {
                        throw $e;
                    }
                    break;
                }
            }
        }
        throw new AiAllProvidersFailedException('No configured AI provider could complete the request.');
    }

    private function log($request, AiResponse $response): void
    {
        AiGeneration::query()->create(['kind' => $request->kind, 'model' => $response->model, 'user_id' => auth()->id(), 'input' => ['provider' => $response->provider, 'messages' => $request->messages], 'output' => $response->data, 'status' => 'completed', 'prompt_tokens' => $response->promptTokens, 'completion_tokens' => $response->completionTokens]);
    }

    private function logFailure(AiRequest $request, string $provider, ?string $model, \Throwable $exception): void
    {
        AiGeneration::query()->create(['kind' => $request->kind, 'model' => $model ?: 'unknown', 'user_id' => auth()->id(), 'input' => ['provider' => $provider, 'messages' => $request->messages], 'status' => 'failed', 'error' => $exception->getMessage()]);
    }
}
