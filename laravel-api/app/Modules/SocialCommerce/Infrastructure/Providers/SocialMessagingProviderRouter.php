<?php

namespace App\Modules\SocialCommerce\Infrastructure\Providers;

use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;

final class SocialMessagingProviderRouter implements SocialMessagingProviderInterface
{
    /** @var array<string, SocialMessagingProviderInterface> */
    private array $providers;

    public function __construct(
        FacebookMessagingProvider $facebook,
        InstagramMessagingProvider $instagram,
        WhatsAppMessagingProvider $whatsapp,
    ) {
        $this->providers = [
            $facebook->channel() => $facebook,
            $instagram->channel() => $instagram,
            $whatsapp->channel() => $whatsapp,
        ];
    }

    public function channel(): string
    {
        return 'router';
    }

    public function verifyWebhook(array $headers, string $rawBody, ?string $secret = null): bool
    {
        return $this->providers['facebook']->verifyWebhook($headers, $rawBody, $secret);
    }

    public function normalizeWebhook(array $payload): array
    {
        return $this->providers['facebook']->normalizeWebhook($payload);
    }

    public function sendMessage(object $connection, string $recipient, string $text): array
    {
        return $this->providerFromChannel((string) $connection->channel)->sendMessage($connection, $recipient, $text);
    }

    public function replyToComment(object $connection, string $commentId, string $text): array
    {
        return $this->providerFromChannel((string) $connection->channel)->replyToComment($connection, $commentId, $text);
    }

    private function providerFromChannel(string $channel): SocialMessagingProviderInterface
    {
        return $this->providers[$channel] ?? throw new SocialCommerceException("Unsupported social channel: {$channel}.");
    }
}
