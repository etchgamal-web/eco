<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Exceptions\ShipmentNotFoundException;
use App\Modules\Shipping\Domain\StateMachines\ShipmentStateMachine;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    public function __construct(private readonly OutboxEventRepositoryInterface $outbox)
    {
    }

    public function find(int $id): object
    {
        $shipment = Shipment::query()->with(['order', 'method', 'events'])->find($id);
        if ($shipment === null) throw new ShipmentNotFoundException('Shipment not found.');
        return $shipment;
    }
    public function findForUser(int $userId, int $id): object
    {
        $shipment = Shipment::query()->with(['order', 'method', 'events'])->where('user_id', $userId)->find($id);
        if ($shipment === null) throw new ShipmentNotFoundException('Shipment not found.');
        return $shipment;
    }
    public function findByIdempotencyKey(string $key): ?object { return Shipment::query()->with(['order', 'method'])->where('idempotency_key', $key)->first(); }
    public function findByProviderReference(string $reference): ?object
    {
        return Shipment::query()->with(['order', 'method', 'events'])
            ->where('tracking_number', $reference)
            ->orWhereJsonContains('metadata->bosta_delivery_id', $reference)
            ->first();
    }
    public function listForUserOrder(int $userId, int $orderId): iterable { return Shipment::query()->with(['method', 'events'])->where('user_id', $userId)->where('order_id', $orderId)->latest()->get(); }
    public function create(array $attributes): object
    {
        return DB::transaction(function () use ($attributes): object {
            try {
                $shipment = Shipment::query()->create($attributes)->load(['order', 'method', 'events']);
                $this->outbox->record('shipment', (int) $shipment->id, 'shipment.create.requested', 'shipment:create:' . $attributes['idempotency_key'], [
                    'shipment_id' => $shipment->id,
                    'idempotency_key' => $attributes['idempotency_key'],
                    'provider_code' => $attributes['provider_code'],
                ]);
                return $shipment;
            } catch (QueryException $exception) {
                $existing = $this->findByIdempotencyKey((string) $attributes['idempotency_key']);
                if ($existing !== null) return $existing;
                throw $exception;
            }
        });
    }
    public function updateProviderData(object $shipment, array $data): object
    {
        if (($data['tracking_number'] ?? null) !== null) {
            ShipmentStateMachine::assert((string) $shipment->status, 'provider_created');
        }
        $shipment->update([
            'tracking_number' => $data['tracking_number'] ?? $shipment->tracking_number,
            'status' => ($data['tracking_number'] ?? null) !== null ? 'provider_created' : $shipment->status,
            'creation_status' => ($data['tracking_number'] ?? null) !== null ? 'created' : $shipment->creation_status,
            'creation_error' => null,
            'created_at_provider' => ($data['tracking_number'] ?? null) !== null ? now() : $shipment->created_at_provider,
            'metadata' => array_merge((array) $shipment->metadata, (array) ($data['metadata'] ?? [])),
        ]);
        return $shipment->fresh(['order', 'method', 'events']);
    }

    public function markCreationPending(object $shipment): object
    {
        $shipment->update(['creation_status' => 'creation_pending', 'creation_error' => null]);
        return $shipment->fresh(['order', 'method', 'events']);
    }

    public function markCreationFailed(object $shipment, string $error): object
    {
        $shipment->update(['creation_status' => 'creation_failed', 'creation_error' => $error]);
        return $shipment->fresh(['order', 'method', 'events']);
    }
    public function updateProviderStatus(object $shipment, string $status, ?string $note = null): object
    {
        return DB::transaction(function () use ($shipment, $status, $note): Shipment {
            $locked = Shipment::query()->lockForUpdate()->find($shipment->id);
            if ($locked === null) throw new ShipmentNotFoundException('Shipment not found.');
            if ($locked->status === $status) return $locked->fresh(['order', 'method', 'events']);
            ShipmentStateMachine::assert((string) $locked->status, $status);
            $from = $locked->status;
            $locked->update(['status' => $status]);
            ShipmentEvent::query()->create(['shipment_id' => $locked->id, 'from_status' => $from, 'to_status' => $status, 'note' => $note]);
            return $locked->fresh(['order', 'method', 'events']);
        });
    }
    public function updateStatus(object $shipment, string $status, ?int $actorId, ?string $note = null): object
    {
        return DB::transaction(function () use ($shipment, $status, $actorId, $note): Shipment {
            $locked = Shipment::query()->lockForUpdate()->find($shipment->id);
            if ($locked === null) throw new ShipmentNotFoundException('Shipment not found.');
            ShipmentStateMachine::assert((string) $locked->status, $status);
            $from = $locked->status;
            $locked->update(['status' => $status]);
            ShipmentEvent::query()->create(['shipment_id' => $locked->id, 'from_status' => $from, 'to_status' => $status, 'actor_id' => $actorId, 'note' => $note]);
            return $locked->fresh(['order', 'method', 'events']);
        });
    }
}
