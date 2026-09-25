<?php

namespace App\Modules\Order\Infrastructure\Observers;

use App\Modules\Order\Infrastructure\Models\OrderActivity;
use App\Modules\Order\Infrastructure\Models\OrderReview;

final class OrderReviewObserver
{
    public function created(OrderReview $review): void
    {
        $this->record($review, 'review_started', ['reviewer_id' => $review->reviewer_id]);
    }

    public function updated(OrderReview $review): void
    {
        if ($review->wasChanged('contacted_at')) {
            $this->record($review, 'customer_contacted', [
                'contact_result' => $review->contact_result,
                'notes' => $review->notes,
            ]);
        }

        if ($review->wasChanged('confirmed_at')) {
            $this->record($review, 'order_confirmed', ['confirmed_by' => $review->confirmed_by]);
        }
    }

    private function record(OrderReview $review, string $event, array $metadata = []): void
    {
        $actor = auth()->user();
        $source = $actor === null ? 'system' : (method_exists($actor, 'hasRole') && $actor->hasRole('customer') ? 'customer' : 'admin');

        OrderActivity::query()->create([
            'order_id' => $review->order_id,
            'event' => $event,
            'actor_id' => $actor?->id,
            'source' => $source,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
