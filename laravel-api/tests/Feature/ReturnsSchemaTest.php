<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ReturnsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_schema_contains_return_and_item_tables(): void
    {
        $this->assertTrue(Schema::hasColumns('order_returns', [
            'id',
            'order_id',
            'user_id',
            'status',
            'reason',
            'notes',
            'refund_amount',
            'rejection_reason',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('order_return_items', [
            'id',
            'return_id',
            'order_item_id',
            'product_id',
            'quantity',
            'unit_price',
            'created_at',
            'updated_at',
        ]));
    }
}
