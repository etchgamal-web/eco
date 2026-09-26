<?php

namespace App\Modules\SocialCommerce\Infrastructure\Persistence;

use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\SocialCommerce\Domain\Contracts\SocialSummaryQueryInterface;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialConversation;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialInteraction;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialMessage;

final class EloquentSocialSummaryQuery implements SocialSummaryQueryInterface
{
    public function summary(): array
    {
        return [
            'orders' => CustomerOrder::query()->count(),
            'new_orders' => CustomerOrder::query()->whereIn('status', ['pending', 'reviewing', 'confirmed'])->count(),
            'messages' => SocialMessage::query()->count(),
            'comments' => SocialInteraction::query()->where('interaction_type', 'comment')->count(),
            'interactions' => SocialInteraction::query()->count(),
            'conversations' => SocialConversation::query()->count(),
            'open_conversations' => SocialConversation::query()->whereIn('status', ['open', 'pending'])->count(),
            'unanswered_comments' => SocialInteraction::query()->where('interaction_type', 'comment')->whereIn('status', ['open', 'pending', 'new'])->count(),
        ];
    }
}
