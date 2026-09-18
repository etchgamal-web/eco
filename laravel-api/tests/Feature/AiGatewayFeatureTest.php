<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiGatewayFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_product_draft_requires_product_permission_and_returns_structured_json(): void
    {
        $user = $this->userWithRole('owner');
        $this->configureProvider(['openai']);
        Http::fake(['https://ai.example.test/*' => Http::response($this->successResponse([
            'name' => 'Test product', 'description' => 'Description', 'slug' => 'test-product',
            'type' => 'simple', 'status' => 'draft', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description',
        ]), 200)]);

        $this->actingAs($user)->postJson('/api/v1/admin/ai/products/draft', [
            'brief' => 'A simple product', 'language' => 'en', 'keywords' => ['test'], 'tone' => 'clear',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Test product')
            ->assertJsonPath('data.status', 'draft');

        self::assertDatabaseHas('ai_generations', ['kind' => 'product_draft', 'status' => 'completed', 'model' => 'test-model']);
    }

    public function test_invalid_ai_input_is_rejected_before_calling_provider(): void
    {
        Http::fake();
        $this->actingAs($this->userWithRole('owner'))
            ->postJson('/api/v1/admin/ai/products/draft', [])
            ->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_gateway_falls_back_to_the_next_provider_and_records_both_attempts(): void
    {
        $this->configureProvider(['openai', 'groq']);
        config(['services.ai.providers.groq' => ['key' => 'groq-key', 'base_url' => 'https://groq.example.test', 'model' => 'groq-model']]);
        Http::fake([
            'https://ai.example.test/*' => Http::response(['error' => ['message' => 'primary down']], 503),
            'https://groq.example.test/*' => Http::response($this->successResponse([
                'reply' => 'Hello', 'language' => 'en', 'confidence' => 0.95, 'needs_human_review' => false,
            ]), 200),
        ]);

        $this->actingAs($this->userWithRole('owner'))->postJson('/api/v1/admin/ai/social/reply-suggestion', [
            'message' => 'Hello', 'channel' => 'facebook', 'language' => 'en',
        ])->assertOk()->assertJsonPath('data.reply', 'Hello');

        self::assertDatabaseHas('ai_generations', ['status' => 'failed', 'model' => 'test-model']);
        self::assertDatabaseHas('ai_generations', ['status' => 'completed', 'model' => 'groq-model']);
    }

    public function test_when_all_providers_fail_http_response_hides_provider_details(): void
    {
        $this->configureProvider(['openai']);
        Http::fake(['https://ai.example.test/*' => Http::response(['error' => ['message' => 'secret provider failure']], 503)]);

        $response = $this->actingAs($this->userWithRole('owner'))->postJson('/api/v1/admin/ai/products/draft', ['brief' => 'test']);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'AI service is temporarily unavailable.')
            ->assertJsonMissing(['message' => 'secret provider failure'])
            ->assertJsonMissing(['message' => 'openai']);
        self::assertSame(1, AiGeneration::query()->where('status', 'failed')->count());
    }

    public function test_provider_output_that_violates_schema_is_rejected(): void
    {
        $this->configureProvider(['openai']);
        Http::fake(['https://ai.example.test/*' => Http::response($this->successResponse(['name' => 'missing required fields']), 200)]);

        $this->actingAs($this->userWithRole('owner'))
            ->postJson('/api/v1/admin/ai/products/draft', ['brief' => 'test'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'AI service is temporarily unavailable.')
            ->assertJsonMissing(['message' => 'missing required fields']);

        self::assertDatabaseHas('ai_generations', ['status' => 'failed', 'kind' => 'product_draft']);
    }

    public function test_confidence_outside_zero_to_one_is_rejected(): void
    {
        $this->configureProvider(['openai']);
        Http::fake(['https://ai.example.test/*' => Http::response($this->successResponse([
            'reply' => 'Hello', 'language' => 'en', 'confidence' => 1.5, 'needs_human_review' => false,
        ]), 200)]);

        $this->actingAs($this->userWithRole('owner'))
            ->postJson('/api/v1/admin/ai/social/reply-suggestion', ['message' => 'Hello', 'channel' => 'facebook'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'AI service is temporarily unavailable.');
    }

    public function test_ai_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/admin/ai/products/draft', ['brief' => 'test'])->assertUnauthorized();
        $this->postJson('/api/v1/admin/ai/social/reply-suggestion', ['message' => 'test', 'channel' => 'facebook'])->assertUnauthorized();
    }

    private function configureProvider(array $order): void
    {
        config([
            'services.ai.provider_order' => $order,
            'services.ai.providers.openai' => ['key' => 'openai-key', 'base_url' => 'https://ai.example.test', 'model' => 'test-model'],
            'services.ai.model' => 'test-model',
        ]);
    }

    private function successResponse(array $data): array
    {
        return ['choices' => [['message' => ['content' => json_encode($data, JSON_THROW_ON_ERROR)]]], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20]];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());
        return $user;
    }
}
