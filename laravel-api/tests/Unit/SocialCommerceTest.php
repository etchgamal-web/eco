<?php

namespace Tests\Unit;

use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;
use App\Modules\SocialCommerce\Domain\ValueObjects\RenderedTemplate;
use App\Modules\SocialCommerce\Infrastructure\Providers\FacebookMessagingProvider;
use App\Modules\SocialCommerce\Infrastructure\Providers\InstagramMessagingProvider;
use App\Modules\SocialCommerce\Infrastructure\Providers\WhatsAppMessagingProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SocialCommerceTest extends TestCase
{
    public function test_template_renders_only_declared_variables(): void
    {
        $rendered = RenderedTemplate::render('Hello {customer_name}, price: {product_price}', [
            'customer_name' => 'Mona',
            'product_price' => '100 EGP',
        ]);

        self::assertSame('Hello Mona, price: 100 EGP', $rendered->text);
    }

    public function test_template_rejects_missing_variables(): void
    {
        $this->expectException(SocialCommerceException::class);

        RenderedTemplate::render('Hello {customer_name}', []);
    }

    public function test_provider_normalizes_a_message_event(): void
    {
        $normalized = (new FacebookMessagingProvider)->normalizeWebhook([
            'event_id' => 'evt-1',
            'entry' => [[
                'id' => 'page-1',
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'id' => 'msg-1',
                            'from' => '201000000000',
                            'conversation_id' => 'conv-1',
                            'text' => ['body' => 'عايز أعرف السعر'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        self::assertSame('evt-1', $normalized['provider_event_id']);
        self::assertSame('conv-1', $normalized['provider_conversation_id']);
        self::assertSame('201000000000', $normalized['provider_customer_id']);
        self::assertSame('عايز أعرف السعر', $normalized['content']);
    }

    public function test_provider_rejects_invalid_webhook_signature(): void
    {
        self::assertFalse((new FacebookMessagingProvider)->verifyWebhook(
            ['x-signature' => 'invalid'],
            '{}',
            'secret',
        ));
    }

    public function test_meta_provider_requires_a_secret_for_webhook_verification(): void
    {
        self::assertFalse((new WhatsAppMessagingProvider)->verifyWebhook(['x-hub-signature-256' => ''], '{}'));
    }

    public function test_meta_provider_sends_whatsapp_payload(): void
    {
        Http::fake(['https://graph.example.test/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200)]);
        config()->set('services.social.whatsapp.send_url', 'https://graph.example.test/messages');

        $result = (new WhatsAppMessagingProvider)->sendMessage((object) [
            'channel' => 'whatsapp',
            'access_token' => 'token',
            'provider_account_id' => 'phone-1',
        ], '201000000000', 'مرحبا');

        Http::assertSent(fn ($request): bool => $request->data()['messaging_product'] === 'whatsapp'
            && $request->data()['to'] === '201000000000'
            && $request->data()['text']['body'] === 'مرحبا');
        self::assertSame('sent', $result['status']);
        self::assertSame('wamid.1', $result['provider_message_id']);
    }

    public function test_facebook_comment_is_normalized_with_comment_id(): void
    {
        $normalized = (new FacebookMessagingProvider)->normalizeWebhook([
            'entry' => [[
                'id' => 'page-1',
                'changes' => [[
                    'value' => ['comment_id' => 'comment-1', 'from' => ['id' => 'user-1'], 'message' => 'السعر؟'],
                ]],
            ]],
        ]);

        self::assertSame('comment', $normalized['interaction_type']);
        self::assertSame('comment-1', $normalized['provider_comment_id']);
        self::assertSame('السعر؟', $normalized['content']);
    }

    public function test_instagram_comment_reply_uses_replies_endpoint(): void
    {
        Http::fake(['https://graph.example.test/*' => Http::response(['id' => 'reply-1'], 200)]);
        config()->set('services.social.instagram.comment_reply_url', 'https://graph.example.test/{comment_id}/replies');

        $result = (new InstagramMessagingProvider)->replyToComment((object) [
            'channel' => 'instagram',
            'access_token' => 'token',
        ], 'comment/1', 'أهلًا');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://graph.example.test/comment%2F1/replies'
            && $request->data()['message'] === 'أهلًا');
        self::assertSame('reply-1', $result['provider_message_id']);
    }
}
