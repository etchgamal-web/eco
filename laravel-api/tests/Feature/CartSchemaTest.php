<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CartSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_schema_contains_the_columns_required_by_cart_lifecycle_code(): void
    {
        $this->assertTrue(Schema::hasColumns('customer_carts', [
            'id',
            'user_id',
            'last_activity_at',
            'abandoned_at',
            'recovered_at',
            'recovery_token',
            'recovery_reminder_count',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('customer_cart_items', [
            'id',
            'cart_id',
            'product_id',
            'variant_id',
            'quantity',
            'created_at',
            'updated_at',
        ]));
    }
}
