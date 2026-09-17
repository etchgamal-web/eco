<?php

namespace Tests\Unit;

use App\Modules\AI\Domain\Contracts\AiProviderInterface;
use App\Modules\AI\Domain\Exceptions\AiAllProvidersFailedException;
use App\Modules\AI\Domain\Exceptions\AiConfigurationException;
use App\Modules\AI\Domain\Exceptions\AiResponseException;
use App\Modules\AI\Domain\ValueObjects\AiRequest;
use App\Modules\AI\Infrastructure\AiGateway;
use App\Modules\AI\Infrastructure\Providers\GeminiProvider;
use App\Modules\AI\Infrastructure\Providers\GroqProvider;
use App\Modules\AI\Infrastructure\Providers\OpenAiProvider;
use App\Modules\AI\Infrastructure\Providers\OpenRouterProvider;
use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AiProviderFailureTest extends TestCase
{
    use RefreshDatabase;

    private function request(): AiRequest
    {
        return new AiRequest('test_generation', [['role' => 'user', 'content' => 'hello']], ['type' => 'object']);
    }

    public function test_missing_provider_configuration_is_typed(): void
    {
        config(['services.ai.providers.openai' => []]);

        $this->expectException(AiConfigurationException::class);
        (new OpenAiProvider())->generate($this->request());
    }

    public function test_invalid_json_response_is_typed(): void
    {
        config(['services.ai.providers.openai' => ['key' => 'test-key', 'base_url' => 'https://ai.test', 'model' => 'test-model']]);
        Http::fake(['https://ai.test/*' => Http::response(['choices' => [['message' => ['content' => 'not-json']]]], 200)]);

        $this->expectException(AiResponseException::class);
        (new OpenAiProvider())->generate($this->request());
    }

    public function test_gateway_reports_exhausted_fallback_without_provider_details(): void
    {
        config(['services.ai.provider_order' => ['openai', 'groq'], 'services.ai.model' => 'test-model']);
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('getByGroup')->with('ai')->once()->andReturn(collect());
        $gateway = new AiGateway($settings, new GeminiProvider(), new OpenAiProvider(), new GroqProvider(), new OpenRouterProvider());

        try {
            $gateway->generate($this->request());
            self::fail('Expected the fallback chain to fail.');
        } catch (AiAllProvidersFailedException $exception) {
            self::assertSame('AI_ALL_PROVIDERS_FAILED', $exception->errorCode());
            self::assertStringNotContainsString('openai', $exception->getMessage());
            self::assertStringNotContainsString('groq', $exception->getMessage());
        }

        self::assertDatabaseHas('ai_generations', ['status' => 'failed', 'kind' => 'test_generation']);
    }
}
