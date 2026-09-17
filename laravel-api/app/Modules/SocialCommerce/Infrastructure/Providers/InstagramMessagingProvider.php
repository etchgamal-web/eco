<?php

namespace App\Modules\SocialCommerce\Infrastructure\Providers;

final class InstagramMessagingProvider extends MetaChannelMessagingProvider
{
    public function channel(): string
    {
        return 'instagram';
    }

    protected function messagePayload(string $recipient, string $text): array
    {
        return ['recipient' => ['id' => $recipient], 'message' => ['text' => $text]];
    }

    protected function commentReplyUrl(string $commentId): string
    {
        $template = (string) config('services.social.instagram.comment_reply_url', '');

        return $template !== '' ? str_replace('{comment_id}', rawurlencode($commentId), $template) : '';
    }
}
