<?php

namespace App\Modules\Inventory\Infrastructure\Persistence;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use App\Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Domain\Exceptions\InvalidStockAdjustmentException;
use App\Modules\Inventory\Domain\Exceptions\InventoryNotFoundException;
use App\Modules\Inventory\Domain\ValueObjects\StockAdjustmentData;
use Illuminate\Support\Facades\DB;

final class EloquentInventoryRepository implements InventoryRepositoryInterface
{
    public function list(): iterable
    {
        return InventoryItem::query()->with(['product', 'variant'])->latest()->get();
    }

    public function find(int $id): InventoryItem
    {
        $item = InventoryItem::query()->with(['product', 'variant'])->find($id);
        if (! $item) {
            throw new InventoryNotFoundException('Inventory item not found.');
        }

        return $item;
    }

    public function adjust(StockAdjustmentData $data, ?int $actorId): InventoryItem
    {
        if ($data->quantity === 0) {
            throw new InvalidStockAdjustmentException('Stock adjustment quantity cannot be zero.');
        }

        return DB::transaction(function () use ($data, $actorId): InventoryItem {
            $item = $this->target($data->productId, $data->variantId);
            $next = $item->on_hand + $data->quantity;
            if ($next < 0 || $next < $item->reserved) {
                throw new InsufficientStockException('Stock cannot be lower than reserved quantity.');
            }
            $item->update(['on_hand' => $next]);
            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id,
                'actor_id' => $actorId,
                'quantity' => $data->quantity,
                'on_hand_after' => $next,
                'reason' => $data->reason,
                'note' => $data->note,
            ]);

            return $item->fresh(['product', 'variant']);
        });
    }

    public function reserve(int $productId, ?int $variantId, int $quantity): InventoryItem
    {
        if ($quantity <= 0) {
            throw new InvalidStockAdjustmentException('Reservation quantity must be positive.');
        }

        return DB::transaction(function () use ($productId, $variantId, $quantity): InventoryItem {
            $item = $this->target($productId, $variantId);
            if ($item->available < $quantity) {
                throw new InsufficientStockException('Insufficient available stock.');
            }
            $item->increment('reserved', $quantity);

            return $item->fresh(['product', 'variant']);
        });
    }

    public function release(int $productId, ?int $variantId, int $quantity): InventoryItem
    {
        if ($quantity <= 0) {
            throw new InvalidStockAdjustmentException('Release quantity must be positive.');
        }

        return DB::transaction(function () use ($productId, $variantId, $quantity): InventoryItem {
            $item = $this->target($productId, $variantId);
            if ($item->reserved < $quantity) {
                throw new InvalidStockAdjustmentException('Cannot release more stock than reserved.');
            }
            $item->decrement('reserved', $quantity);

            return $item->fresh(['product', 'variant']);
        });
    }

    public function commit(int $productId, ?int $variantId, int $quantity, ?int $actorId = null): InventoryItem
    {
        if ($quantity <= 0) {
            throw new InvalidStockAdjustmentException('Commit quantity must be positive.');
        }

        return DB::transaction(function () use ($productId, $variantId, $quantity, $actorId): InventoryItem {
            $item = $this->target($productId, $variantId);
            if ($item->reserved < $quantity || $item->on_hand < $quantity) {
                throw new InsufficientStockException('Cannot commit more stock than reserved.');
            }
            $next = $item->on_hand - $quantity;
            $item->update(['on_hand' => $next, 'reserved' => $item->reserved - $quantity]);
            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id,
                'actor_id' => $actorId,
                'quantity' => -$quantity,
                'on_hand_after' => $next,
                'reason' => 'sale',
                'note' => 'Order shipped',
            ]);

            return $item->fresh(['product', 'variant']);
        });
    }

    private function target(int $productId, ?int $variantId): InventoryItem
    {
        // Locking the parent also serializes first-time creation (including the
        // nullable-variant case, which SQL unique indexes treat differently).
        Product::query()->lockForUpdate()->findOrFail($productId);
        $query = InventoryItem::query()->where('product_id', $productId);
        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            if (! ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->exists()) {
                throw new InventoryNotFoundException('Product variant does not belong to product.');
            }
            $query->where('variant_id', $variantId);
        }

        // The composite unique key prevents duplicate rows; the row lock makes every
        // availability check and reservation a serialized read-modify-write operation.
        $item = $query->first();
        if ($item === null) {
            $item = InventoryItem::query()->firstOrCreate([
                'product_id' => $productId,
                'variant_id' => $variantId,
            ]);
        }

        return InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
    }
}
