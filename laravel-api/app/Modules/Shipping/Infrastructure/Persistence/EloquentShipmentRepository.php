<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Data\OutboxMessage;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Exceptions\ShipmentNotFoundException;
use App\Modules\Shipping\Domain\StateMachines\ShipmentStateMachine;
use App\Modules\Shipping\Infrastructure\Models\Shipment;
use App\Modules\Shipping\Infrastructure\Models\ShipmentEvent;
use App\Modules\Shipping\Infrastructure\Models\ShipmentOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    public function __construct(private readonly OutboxRepositoryInterface $outbox) {}

    public function find(int $id): object
    {
        $shipment = Shipment::query()->with(['order', 'method', 'events'])->find($id);
        if ($shipment === null) {
            throw new ShipmentNotFoundException('Shipment not found.');
        }

        return $shipment;
    }

    public function findForUser(int $userId, int $id): object
    {
        $shipment = Shipment::query()->with(['order', 'method', 'events'])->where('user_id', $userId)->find($id);
        if ($shipment === null) {
            throw new ShipmentNotFoundException('Shipment not found.');
        }

        return $shipment;
    }

    public function findByIdempotencyKey(string $key): ?object
    {
        return Shipment::query()->with(['order', 'method'])->where('idempotency_key', $key)->first();
    }

    public function findByProviderReference(string $reference): ?object
    {
        return Shipment::query()->with(['order', 'method', 'events'])
            ->where('tracking_number', $reference)
            ->orWhereJsonContains('metadata->bosta_delivery_id', $reference)
            ->first();
    }

    public function findByPublicTrackingToken(string $token): ?object
    {
        return Shipment::query()->with(['events' => fn ($query) => $query->latest()->limit(20)])->where('public_tracking_token', $token)->first();
    }

    public function listForUserOrder(int $userId, int $orderId): iterable
    {
        return Shipment::query()->with(['method', 'events'])->where('user_id', $userId)->where('order_id', $orderId)->latest()->get();
    }

    public function create(array $attributes): object
    {
        return DB::transaction(function () use ($attributes): object {
            try {
                $shipment = Shipment::query()->create($attributes)->load(['order', 'method', 'events']);
            } catch (QueryException $exception) {
                $existing = $this->findByIdempotencyKey((string) $attributes['idempotency_key']);
                if ($existing !== null) {
                    return $existing;
                }
                throw $exception;
            }

            $this->outbox->add(new OutboxMessage('shipment.create.requested', 'shipment', (int) $shipment->id, [
                'shipment_id' => $shipment->id,
                'idempotency_key' => $attributes['idempotency_key'],
                'provider_code' => $attributes['provider_code'],
            ], deduplicationKey: 'shipment:create:'.$attributes['idempotency_key']));

            return $shipment;
        });
    }

    public function updateProviderData(object $shipment, array $data, ?string $operationLeaseToken = null): object
    {
        if ($operationLeaseToken !== null) {
            return DB::transaction(function () use ($shipment, $data, $operationLeaseToken): object {
                $owns = ShipmentOperation::query()->lockForUpdate()->where('shipment_id', $shipment->id)->where('operation', 'create')->where('lease_token', $operationLeaseToken)->where('lease_expires_at', '>', now())->exists();
                if (! $owns) throw new \RuntimeException('Shipment operation lease is no longer valid.');
                $locked = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
                return $this->persistProviderData($locked, $data);
            });
        }
        return $this->persistProviderData($shipment, $data);
    }

    private function persistProviderData(object $shipment, array $data): object
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
            if ($locked === null) {
                throw new ShipmentNotFoundException('Shipment not found.');
            }
            if ($locked->status === $status) {
                return $locked->fresh(['order', 'method', 'events']);
            }
            ShipmentStateMachine::assert((string) $locked->status, $status);
            $from = $locked->status;
            $locked->update(['status' => $status]);
            ShipmentEvent::query()->create(['shipment_id' => $locked->id, 'from_status' => $from, 'to_status' => $status, 'note' => $note]);

            return $locked->fresh(['order', 'method', 'events']);
        });
    }

    public function updateStatus(object $shipment, string $status, ?int $actorId, ?string $note = null, ?string $operationLeaseToken = null): object
    {
        if ($operationLeaseToken !== null) {
            return DB::transaction(function () use ($shipment, $status, $actorId, $note, $operationLeaseToken): Shipment {
                $owns = ShipmentOperation::query()->lockForUpdate()->where('shipment_id', $shipment->id)->where('operation', 'create')->where('lease_token', $operationLeaseToken)->where('lease_expires_at', '>', now())->exists();
                if (! $owns) throw new \RuntimeException('Shipment operation lease is no longer valid.');
                $locked = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
                ShipmentStateMachine::assert((string) $locked->status, $status);
                $from = $locked->status;
                $locked->update(['status' => $status]);
                ShipmentEvent::query()->create(['shipment_id' => $locked->id, 'from_status' => $from, 'to_status' => $status, 'actor_id' => $actorId, 'note' => $note]);
                return $locked->fresh(['order', 'method', 'events']);
            });
        }
        return DB::transaction(function () use ($shipment, $status, $actorId, $note): Shipment {
            $locked = Shipment::query()->lockForUpdate()->find($shipment->id);
            if ($locked === null) {
                throw new ShipmentNotFoundException('Shipment not found.');
            }
            ShipmentStateMachine::assert((string) $locked->status, $status);
            $from = $locked->status;
            $locked->update(['status' => $status]);
            ShipmentEvent::query()->create(['shipment_id' => $locked->id, 'from_status' => $from, 'to_status' => $status, 'actor_id' => $actorId, 'note' => $note]);

            return $locked->fresh(['order', 'method', 'events']);
        });
    }
}
