<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Models\PaymentWebhookEvent;
use App\Modules\Payment\Domain\Contracts\PaymentWebhookEventRepositoryInterface;

final class EloquentPaymentWebhookEventRepository implements PaymentWebhookEventRepositoryInterface
{
    public function recordOrGet(array $attributes): object
    {
        try {
            return PaymentWebhookEvent::query()->firstOrCreate(
                ['provider' => $attributes['provider'], 'event_id' => $attributes['event_id']],
                $attributes,
            );
        } catch (\Throwable) {
            return PaymentWebhookEvent::query()
                ->where('provider', $attributes['provider'])
                ->where('event_id', $attributes['event_id'])
                ->firstOrFail();
        }
    }

    public function markProcessed(string $provider, string $eventId): void
    {
        PaymentWebhookEvent::query()
            ->where('provider', $provider)
            ->where('event_id', $eventId)
            ->update(['status' => 'processed', 'processed_at' => now()]);
    }
}
