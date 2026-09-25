<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialAutomationRule;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialConnection;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialConversation;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialWebhookEvent;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class SocialCommerceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_meta_webhook_verification_returns_challenge_only_for_valid_token(): void
    {
        config(['services.social.facebook.verify_token' => 'verify-secret']);

        $this->getJson('/api/v1/social/webhooks/facebook?hub_mode=subscribe&hub_verify_token=verify-secret&hub_challenge=challenge-123')
            ->assertOk()
            ->assertContent('"challenge-123"');

        $this->getJson('/api/v1/social/webhooks/facebook?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=challenge-123')
            ->assertForbidden()
            ->assertJsonPath('message', 'Verification failed.');
    }

    public function test_invalid_signature_is_rejected_and_valid_webhook_is_idempotent(): void
    {
        $connection = SocialConnection::query()->create([
            'channel' => 'facebook', 'name' => 'Test Page', 'provider_account_id' => 'page-1',
            'webhook_secret' => 'secret', 'is_active' => true,
        ]);
        $payload = ['event_id' => 'event-1', 'entry' => [[
            'id' => 'page-1', 'changes' => [[
                'value' => ['messages' => [[
                    'id' => 'message-1', 'from' => 'customer-1', 'conversation_id' => 'conversation-1',
                    'text' => ['body' => 'Hello'],
                ]]],
            ]],
        ]]];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'secret');

        $this->postWebhook('facebook', $raw, 'sha256=invalid')->assertUnauthorized();

        $request = fn () => $this->postWebhook('facebook', $raw, $signature);

        $request()->assertOk()->assertJsonPath('received', true);
        $request()->assertOk()->assertJsonPath('received', true);

        self::assertSame(1, SocialWebhookEvent::query()->where('provider_event_id', 'event-1')->count());
        self::assertSame(1, SocialConversation::query()->where('provider_conversation_id', 'conversation-1')->count());
        $connection->delete();
    }

    public function test_webhook_endpoints_accept_all_supported_meta_channels(): void
    {
        $payload = ['event_id' => 'multi-channel-event', 'entry' => [[
            'id' => 'account-1', 'changes' => [[
                'value' => ['messages' => [[
                    'id' => 'message-multi', 'from' => 'customer-1', 'conversation_id' => 'conversation-multi',
                    'text' => ['body' => 'Hello'],
                ]]],
            ]],
        ]]];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'secret');

        foreach (['facebook', 'instagram', 'whatsapp'] as $channel) {
            SocialConnection::query()->create([
                'channel' => $channel, 'name' => $channel, 'provider_account_id' => 'account-1',
                'webhook_secret' => 'secret', 'is_active' => true,
            ]);
            $this->postWebhook($channel, $raw, $signature)
                ->assertOk()
                ->assertJsonPath('received', true);
        }
    }

    public function test_keyword_automation_does_not_reply_in_manual_or_paused_modes(): void
    {
        Http::fake();
        SocialConnection::query()->create([
            'channel' => 'facebook', 'name' => 'Test Page', 'provider_account_id' => 'page-1',
            'access_token' => 'token', 'webhook_secret' => 'secret', 'is_active' => true,
        ]);
        SocialAutomationRule::query()->create([
            'name' => 'Price rule', 'channel' => 'facebook', 'conditions' => ['keywords' => ['price']],
            'actions' => [['type' => 'send_message', 'message' => 'Our price is 100.']], 'is_active' => true,
        ]);

        foreach (['manual', 'paused'] as $mode) {
            $conversationId = $mode.'-conversation';
            SocialConversation::query()->create([
                'channel' => 'facebook', 'provider_conversation_id' => $conversationId,
                'provider_customer_id' => 'customer-'.$mode, 'mode' => $mode,
            ]);
            $payload = ['event_id' => $mode.'-event', 'entry' => [[
                'id' => 'page-1', 'changes' => [[
                    'value' => ['messages' => [[
                        'id' => $mode.'-message', 'from' => 'customer-'.$mode, 'conversation_id' => $conversationId,
                        'text' => ['body' => 'What is the price?'],
                    ]]],
                ]],
            ]]];
            $raw = json_encode($payload, JSON_THROW_ON_ERROR);
            $this->postWebhook('facebook', $raw, 'sha256='.hash_hmac('sha256', $raw, 'secret'))
                ->assertOk();
        }

        Http::assertNothingSent();
    }

    public function test_meta_api_failure_is_returned_without_provider_error_details(): void
    {
        config(['services.social.facebook.send_url' => 'https://graph.example.test/messages']);
        Http::fake(['https://graph.example.test/*' => Http::response(['error' => ['message' => 'secret Meta failure']], 500)]);
        SocialConnection::query()->create([
            'channel' => 'facebook', 'name' => 'Test Page', 'provider_account_id' => 'page-1',
            'access_token' => 'token', 'webhook_secret' => 'secret', 'is_active' => true,
        ]);
        SocialAutomationRule::query()->create([
            'name' => 'Price rule', 'channel' => 'facebook', 'conditions' => ['keywords' => ['price']],
            'actions' => [['type' => 'send_message', 'message' => 'Our price is 100.']], 'is_active' => true,
        ]);
        $conversation = SocialConversation::query()->create([
            'channel' => 'facebook', 'provider_conversation_id' => 'failure-conversation',
            'provider_customer_id' => 'customer-failure', 'mode' => 'automated',
        ]);
        $payload = ['event_id' => 'failure-event', 'entry' => [[
            'id' => 'page-1', 'changes' => [[
                'value' => ['messages' => [[
                    'id' => 'failure-message', 'from' => 'customer-failure', 'conversation_id' => $conversation->provider_conversation_id,
                    'text' => ['body' => 'What is the price?'],
                ]]],
            ]],
        ]]];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->postWebhook('facebook', $raw, 'sha256='.hash_hmac('sha256', $raw, 'secret'))
            ->assertUnprocessable()
            ->assertJsonMissing(['message' => 'secret Meta failure']);
    }

    public function test_social_admin_routes_require_the_expected_permission(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'support_agent')->firstOrFail());

        $this->actingAs($user)->getJson('/api/v1/admin/social/connections')->assertForbidden();
        $this->actingAs($user)->getJson('/api/v1/admin/social/interactions')->assertForbidden();
    }

    private function postWebhook(string $channel, string $raw, string $signature): TestResponse
    {
        return $this->call('POST', "/api/v1/social/webhooks/{$channel}", [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $raw);
    }
}
