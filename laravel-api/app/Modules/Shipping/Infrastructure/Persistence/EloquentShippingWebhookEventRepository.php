<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Models\ShippingWebhookEvent;
use App\Modules\Shipping\Domain\Contracts\ShippingWebhookEventRepositoryInterface;
use Illuminate\Database\QueryException;

final class EloquentShippingWebhookEventRepository implements ShippingWebhookEventRepositoryInterface
{
    public function recordOrGet(array $attributes): object
    {
        try {
            return ShippingWebhookEvent::query()->create($attributes);
        } catch (QueryException $exception) {
            $existing = $this->queryFor($attributes)->first();
            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    public function markProcessed(object $event): object
    {
        $event->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processing_error' => null,
        ]);

        return $event->fresh();
    }

    public function markFailed(object $event, string $error): object
    {
        $event->update([
            'status' => 'failed',
            'processing_error' => $error,
        ]);

        return $event->fresh();
    }

    private function queryFor(array $attributes): mixed
    {
        return ShippingWebhookEvent::query()
            ->where('provider', $attributes['provider'])
            ->where('event_id', $attributes['event_id']);
    }
}
