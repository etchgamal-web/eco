<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class InventorySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_schema_contains_item_and_movement_tables(): void
    {
        $this->assertTrue(Schema::hasColumns('inventory_items', [
            'id',
            'product_id',
            'variant_id',
            'on_hand',
            'reserved',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('inventory_movements', [
            'id',
            'inventory_item_id',
            'actor_id',
            'quantity',
            'on_hand_after',
            'reason',
            'note',
            'created_at',
            'updated_at',
        ]));
    }
}
