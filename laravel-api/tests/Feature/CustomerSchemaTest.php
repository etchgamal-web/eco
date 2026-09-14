<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CustomerSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_tables_contain_the_columns_used_by_the_module(): void
    {
        $expected = [
            'customer_addresses' => [
                'id', 'user_id', 'label', 'recipient_name', 'phone', 'address_line1',
                'address_line2', 'city', 'state', 'postal_code', 'country', 'is_default',
                'created_at', 'updated_at',
            ],
            'customer_carts' => [
                'id', 'user_id', 'last_activity_at', 'abandoned_at', 'recovered_at',
                'recovery_token', 'recovery_reminder_count', 'created_at', 'updated_at',
            ],
            'customer_cart_items' => [
                'id', 'cart_id', 'product_id', 'variant_id', 'quantity', 'created_at', 'updated_at',
            ],
            'customer_orders' => [
                'id', 'user_id', 'status', 'total_amount', 'subtotal_amount', 'discount_amount',
                'coupon_code', 'tax_amount', 'tax_rate', 'tax_rule_id', 'shipping_amount',
                'currency', 'shipping_address', 'idempotency_key', 'created_at', 'updated_at',
            ],
            'customer_order_items' => [
                'id', 'order_id', 'product_id', 'variant_id', 'name', 'sku', 'quantity',
                'unit_price', 'discount_amount', 'tax_amount', 'total_amount', 'created_at', 'updated_at',
            ],
            'customer_wishlists' => [
                'id', 'user_id', 'product_id', 'created_at', 'updated_at',
            ],
            'customer_preferences' => [
                'id', 'user_id', 'data', 'created_at', 'updated_at',
            ],
            'customer_notifications' => [
                'id', 'user_id', 'type', 'title', 'body', 'read_at', 'created_at', 'updated_at',
            ],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Schema mismatch in {$table}.");
        }
    }
}
