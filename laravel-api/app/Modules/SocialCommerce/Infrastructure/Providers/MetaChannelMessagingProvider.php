<?php

namespace App\Modules\SocialCommerce\Infrastructure\Providers;

use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;
use Illuminate\Support\Facades\Http;

abstract class MetaChannelMessagingProvider implements SocialMessagingProviderInterface
{
    abstract public function channel(): string;

    public function verifyWebhook(array $headers, string $rawBody, ?string $secret = null): bool
    {
        if (! $secret) {
            return false;
        }

        $provided = $headers['x-hub-signature-256'][0] ?? $headers['x-signature'][0] ?? $headers['x-hub-signature-256'] ?? $headers['x-signature'] ?? null;
        if (! is_string($provided) || $provided === '') {
            return false;
        }

        $provided = preg_replace('/^sha256=/', '', $provided);

        return is_string($provided) && hash_equals(hash_hmac('sha256', $rawBody, $secret), $provided);
    }

    public function normalizeWebhook(array $payload): array
    {
        $entry = $payload['entry'][0] ?? $payload;
        $change = $entry['changes'][0]['value'] ?? $entry;
        $message = $change['messages'][0] ?? $change['message'] ?? $change;
        $sender = $message['from'] ?? $message['sender']['id'] ?? $change['from']['id'] ?? null;
        $interactionType = $message['type'] ?? (isset($change['comments']) || isset($change['comment_id']) ? 'comment' : 'webhook_event');

        return [
            'provider_event_id' => $payload['event_id'] ?? $entry['id'] ?? null,
            'provider_conversation_id' => $message['conversation_id'] ?? $message['thread_id'] ?? $entry['id'] ?? null,
            'provider_customer_id' => $sender,
            'provider_message_id' => $message['id'] ?? null,
            'interaction_type' => $interactionType,
            'content' => $message['text']['body'] ?? $message['text'] ?? $message['body'] ?? $change['message'] ?? null,
            'provider_comment_id' => $interactionType === 'comment' ? ($message['comment_id'] ?? $change['comment_id'] ?? $message['id'] ?? null) : null,
            'customer_id' => null,
            'product_id' => null,
        ];
    }

    public function sendMessage(object $connection, string $recipient, string $text): array
    {
        if (! $connection->access_token || ! $connection->provider_account_id) {
            return ['status' => 'prepared', 'recipient' => $recipient, 'channel' => $connection->channel];
        }

        $channel = $this->channel();
        $url = (string) config("services.social.{$channel}.send_url", '');
        if ($url === '') {
            throw new SocialCommerceException("No send URL configured for {$channel}.");
        }

        $response = Http::timeout((int) config('services.social.timeout', 15))
            ->withToken($connection->access_token)
            ->acceptJson()
            ->post($url, $this->messagePayload($recipient, $text));

        if ($response->failed()) {
            throw new SocialCommerceException('Social provider message send failed: '.$response->status());
        }

        return [
            'status' => 'sent',
            'provider_message_id' => $response->json('messages.0.id') ?? $response->json('message_id') ?? $response->json('id'),
            'response' => $response->json(),
        ];
    }

    public function replyToComment(object $connection, string $commentId, string $text): array
    {
        if (! $connection->access_token) {
            return ['status' => 'prepared', 'comment_id' => $commentId, 'channel' => $this->channel()];
        }

        $url = $this->commentReplyUrl($commentId);
        if ($url === '') {
            throw new SocialCommerceException("No comment reply URL configured for {$this->channel()}.");
        }

        $response = Http::timeout((int) config('services.social.timeout', 15))
            ->withToken($connection->access_token)
            ->acceptJson()
            ->post($url, ['message' => $text]);

        if ($response->failed()) {
            throw new SocialCommerceException('Social comment reply failed: '.$response->status());
        }

        return [
            'status' => 'sent',
            'provider_message_id' => $response->json('id') ?? $response->json('comment_id'),
            'comment_id' => $commentId,
            'response' => $response->json(),
        ];
    }

    abstract protected function messagePayload(string $recipient, string $text): array;

    abstract protected function commentReplyUrl(string $commentId): string;
}
