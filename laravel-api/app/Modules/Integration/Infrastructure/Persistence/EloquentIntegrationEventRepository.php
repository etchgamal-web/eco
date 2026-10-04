<?php

namespace App\Modules\Integration\Infrastructure\Persistence;

use App\Modules\Integration\Domain\Contracts\IntegrationEventRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use App\Modules\Auth\Domain\Exceptions\AuthorizationException;

final class EloquentIntegrationEventRepository implements IntegrationEventRepositoryInterface
{
    private const SOURCES = ['payment', 'shipping', 'social'];

    public function list(array $filters): array
    {
        $sources = $filters['source'] ? [$filters['source']] : self::SOURCES;
        $rows = collect();
        foreach ($sources as $source) {
            $table = $this->table($source);
            $query = DB::table($table)->select('*')->orderByDesc('created_at')->limit(100);
            if (! empty($filters['status'])) $query->where('status', $filters['status']);
            if (! empty($filters['provider'])) $query->where($source === 'social' ? 'channel' : 'provider', $filters['provider']);
            foreach ($query->get() as $row) $rows->push($this->normalize($source, $row));
        }
        return $rows->sortByDesc('created_at')->values()->all();
    }

    public function retry(string $source, int $id): array
    {
        $table = $this->table($source);
        $event = DB::table($table)->where('id', $id)->first();
        if (! $event) throw new AuthorizationException('Integration event was not found.');
        if (! in_array($event->status, ['failed', 'error', 'received', 'retrying'], true)) throw new AuthorizationException('Only failed or received events can be retried.');
        $updates = ['status' => 'retrying', 'processed_at' => null, 'updated_at' => now()];
        if ($source !== 'social') $updates['processing_error'] = null;
        DB::table($table)->where('id', $id)->update($updates);
        return $this->normalize($source, DB::table($table)->where('id', $id)->first());
    }

    private function table(string $source): string
    {
        if (! in_array($source, self::SOURCES, true)) throw new AuthorizationException('Invalid integration source.');
        return ['payment' => 'payment_webhook_events', 'shipping' => 'shipping_webhook_events', 'social' => 'social_webhook_events'][$source];
    }

    private function normalize(string $source, object $row): array
    {
        return ['id' => (int) $row->id, 'source' => $source, 'provider' => $row->provider ?? $row->channel ?? null, 'event_id' => $row->event_id ?? $row->provider_event_id, 'event_type' => $row->event_type, 'status' => $row->status, 'reference' => $row->payment_reference ?? $row->shipment_reference ?? null, 'error' => $row->processing_error ?? null, 'processed_at' => $row->processed_at, 'created_at' => $row->created_at];
    }
}
