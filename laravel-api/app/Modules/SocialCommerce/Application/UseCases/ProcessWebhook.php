<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\AutomationRuleRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\InvalidWebhookSignatureException;
use App\Modules\SocialCommerce\Domain\ValueObjects\RenderedTemplate;

final class ProcessWebhook
{
    public function __construct(
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly SocialMessagingProviderInterface $provider,
        private readonly AutomationRuleRepositoryInterface $rules,
        private readonly MessageTemplateRepositoryInterface $templates,
    ) {}

    public function execute(string $channel, array $payload, array $headers, string $rawBody): object
    {
        $connection = $this->connections->activeForChannel($channel);
        $secret = $connection?->webhook_secret;
        if (! $this->provider->verifyWebhook($headers, $rawBody, $secret)) {
            throw new InvalidWebhookSignatureException('Invalid webhook signature.');
        }
        $normalized = $this->provider->normalizeWebhook($payload);
        $eventId = (string) ($normalized['provider_event_id'] ?? hash('sha256', $rawBody));
        $existing = $this->interactions->findWebhookEvent($channel, $eventId);
        if ($existing) {
            return $existing;
        }
        $event = $this->interactions->recordWebhookEvent([
            'channel' => $channel,
            'provider_event_id' => $eventId,
            'event_type' => $normalized['interaction_type'] ?? 'webhook_event',
            'payload' => $payload,
            'status' => 'received',
        ]);
        $conversation = $this->interactions->findOrCreateConversation([
            'channel' => $channel,
            'provider_conversation_id' => $normalized['provider_conversation_id'] ?? null,
            'provider_customer_id' => $normalized['provider_customer_id'] ?? null,
            'customer_id' => $normalized['customer_id'] ?? null,
            'product_id' => $normalized['product_id'] ?? null,
            'mode' => 'manual',
        ]);
        $this->interactions->addMessage([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'sender' => 'customer',
            'provider_message_id' => $normalized['provider_message_id'] ?? null,
            'body' => $normalized['content'] ?? null,
            'metadata' => $normalized,
        ]);
        $this->interactions->create([
            'channel' => $channel,
            'interaction_type' => $normalized['interaction_type'] ?? 'webhook_event',
            'provider_interaction_id' => $normalized['provider_message_id'] ?? $eventId,
            'conversation_id' => $conversation->id,
            'customer_id' => $normalized['customer_id'] ?? null,
            'product_id' => $normalized['product_id'] ?? null,
            'content' => $normalized['content'] ?? null,
            'status' => 'new',
            'metadata' => $normalized,
        ]);
        $this->runAutomation($channel, $conversation, $normalized, $eventId);
        $this->interactions->markWebhookProcessed($event);

        return $conversation;
    }

    private function runAutomation(string $channel, object $conversation, array $normalized, string $eventId): void
    {
        $content = (string) ($normalized['content'] ?? '');
        if ($conversation->mode !== 'automated' || trim($content) === '') {
            return;
        }
        foreach ($this->rules->activeFor($channel) as $rule) {
            $keywords = array_map('mb_strtolower', (array) ($rule->conditions['keywords'] ?? []));
            $matches = $keywords === [] || count(array_filter(
                $keywords,
                fn (string $keyword): bool => str_contains(mb_strtolower($content), $keyword),
            )) > 0;
            if (! $matches) {
                continue;
            }
            $key = hash('sha256', $eventId.':'.$rule->id);
            if ($this->rules->executionExists($key)) {
                continue;
            }
            $action = (array) ($rule->actions[0] ?? $rule->actions);
            $messageText = $this->actionMessage($action, $normalized, $conversation);
            if ($messageText === null) {
                continue;
            }
            if (($action['type'] ?? null) === 'reply_to_comment'
                && (($normalized['interaction_type'] ?? null) !== 'comment' || empty($normalized['provider_comment_id']))) {
                continue;
            }
            $connection = $this->connections->activeForChannel($channel);
            if (! $connection) {
                continue;
            }
            $result = ($action['type'] ?? null) === 'reply_to_comment'
                ? $this->provider->replyToComment($connection, (string) ($normalized['provider_comment_id'] ?? ''), $messageText)
                : $this->provider->sendMessage($connection, (string) $conversation->provider_customer_id, $messageText);
            $message = $this->interactions->addMessage([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'sender' => 'automation',
                'responder_type' => 'ai',
                'responder_id' => null,
                'responder_name' => 'AI Automation',
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'body' => $messageText,
                'metadata' => $result,
            ]);
            $this->rules->recordExecution([
                'rule_id' => $rule->id,
                'conversation_id' => $conversation->id,
                'idempotency_key' => $key,
                'status' => 'completed',
                'result' => ['message_id' => $message->id, 'provider' => $result, 'responder' => ['type' => 'ai', 'id' => null, 'name' => 'AI Automation']],
            ]);
        }
    }

    private function actionMessage(array $action, array $normalized, object $conversation): ?string
    {
        $templateId = $action['template_id'] ?? null;
        $template = $templateId ? $this->templates->find((int) $templateId) : null;
        $body = $template?->body ?? $action['message'] ?? null;
        if (! is_string($body)) {
            return null;
        }

        $variables = array_merge((array) ($normalized['variables'] ?? []), [
            'customer_name' => $normalized['customer_name'] ?? null,
            'product_name' => $normalized['product_name'] ?? null,
            'conversation_id' => $conversation->id,
        ]);

        return RenderedTemplate::render($body, $variables)->text;
    }
}
