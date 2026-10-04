<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Inventory\Infrastructure\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('email', 'admin@example.com')->value('id');
        $now = now();

        foreach ([
            ['product' => 'recycled-cotton-tote', 'sku' => null, 'on_hand' => 42, 'reserved' => 2],
            ['product' => 'bamboo-toothbrush-set', 'sku' => null, 'on_hand' => 120, 'reserved' => 8],
            ['product' => 'glass-storage-set', 'sku' => 'ECO-STORAGE-CLEAR', 'on_hand' => 14, 'reserved' => 2],
            ['product' => 'glass-storage-set', 'sku' => 'ECO-STORAGE-AMBER', 'on_hand' => 7, 'reserved' => 0],
            ['product' => 'solar-garden-lantern', 'sku' => null, 'on_hand' => 4, 'reserved' => 1],
        ] as $stock) {
            $product = Product::query()->where('slug', $stock['product'])->first();

            if ($product === null) {
                continue;
            }

            $variant = $stock['sku'] === null
                ? null
                : ProductVariant::query()->where('sku', $stock['sku'])->first();

            if ($stock['sku'] !== null && $variant === null) {
                continue;
            }

            $item = InventoryItem::query()->updateOrCreate(
                ['product_id' => $product->id, 'variant_id' => $variant?->id],
                ['on_hand' => $stock['on_hand'], 'reserved' => $stock['reserved']]
            );

            $label = $stock['sku'] ?? $stock['product'];
            $note = 'Initial stock for '.$label.'.';

            DB::table('inventory_movements')->updateOrInsert(
                [
                    'inventory_item_id' => $item->id,
                    'reason' => 'demo_initial_stock',
                    'note' => $note,
                ],
                [
                    'actor_id' => $adminId,
                    'quantity' => $stock['on_hand'],
                    'on_hand_after' => $stock['on_hand'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
