<?php

namespace App\Modules\SocialCommerce\Infrastructure\Providers;

use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;

final class WhatsAppMessagingProvider extends MetaChannelMessagingProvider
{
    public function channel(): string
    {
        return 'whatsapp';
    }

    protected function messagePayload(string $recipient, string $text): array
    {
        return ['messaging_product' => 'whatsapp', 'to' => $recipient, 'type' => 'text', 'text' => ['body' => $text]];
    }

    protected function commentReplyUrl(string $commentId): string
    {
        throw new SocialCommerceException('WhatsApp does not support social comment replies.');
    }
}
