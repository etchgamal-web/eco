<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_seeder_populates_domain_fixtures_idempotently(): void
    {
        $this->seed();

        foreach ([
            ['users', ['email' => 'admin@example.com']],
            ['users', ['email' => 'customer@example.com']],
            ['roles', ['slug' => 'admin']],
            ['permissions', ['slug' => 'products.view']],
            ['brands', ['slug' => 'eco-home']],
            ['categories', ['slug' => 'home-essentials']],
            ['attributes', ['name' => 'Color']],
            ['attribute_values', ['value' => 'White']],
            ['attribute_values', ['value' => 'Clear']],
            ['product_variants', ['sku' => 'ECO-BOTTLE-WHITE-BAMBOO']],
            ['products', ['slug' => 'reusable-bamboo-bottle']],
            ['products', ['slug' => 'recycled-cotton-tote', 'type' => 'simple', 'price' => 3200]],
            ['products', ['slug' => 'glass-storage-set', 'type' => 'variable']],
            ['products', ['slug' => 'bamboo-toothbrush-set']],
            ['products', ['slug' => 'solar-garden-lantern']],
            ['product_variants', ['sku' => 'ECO-STORAGE-CLEAR', 'status' => 'active']],
            ['product_media', ['path' => 'demo/reusable-bamboo-bottle.jpg']],
            ['product_reviews', ['title' => 'Reliable everyday bottle', 'status' => 'approved']],
            ['customer_addresses', ['label' => 'home', 'recipient_name' => 'Demo Customer']],
            ['customer_cart_items', ['quantity' => 2]],
            ['inventory_items', ['on_hand' => 49, 'reserved' => 3]],
            ['coupons', ['code' => 'WELCOME10', 'type' => 'percent']],
            ['tax_rules', ['name' => 'Egypt VAT Demo', 'country' => 'EG']],
            ['customer_orders', ['order_number' => 'DEMO-2026-0001', 'status' => 'delivered']],
            ['customer_order_items', ['sku' => 'ECO-BOTTLE-WHITE-BAMBOO', 'quantity' => 2]],
            ['coupon_usages', ['discount_amount' => 900]],
            ['order_activities', ['event' => 'order_delivered']],
            ['order_reviews', ['contact_result' => 'confirmed']],
            ['payments', ['idempotency_key' => 'seed-payment-demo-001', 'status' => 'partially_refunded']],
            ['payment_operations', ['idempotency_key' => 'seed-payment-confirm-demo-001', 'status' => 'confirmed']],
            ['payment_webhook_events', ['event_id' => 'seed-payment-event-0001', 'status' => 'processed']],
            ['provider_circuit_breakers', ['provider' => 'demo-payment-provider', 'failure_count' => 0]],
            ['shipping_methods', ['code' => 'demo-standard']],
            ['shipping_providers', ['code' => 'demo-carrier', 'is_active' => false]],
            ['shipping_pricing_plans', ['name' => 'Demo Cairo Rates', 'is_active' => false]],
            ['shipping_pricing_rules', ['zone_code' => 'CAIRO']],
            ['shipping_fee_options', ['code' => 'demo-delivery']],
            ['shipments', ['tracking_number' => 'DEMO-TRK-0001', 'status' => 'delivered']],
            ['shipment_events', ['to_status' => 'delivered']],
            ['shipment_operations', ['idempotency_key' => 'seed-shipment-create-demo-001', 'status' => 'completed']],
            ['shipment_pricing_snapshots', ['total_expected_cost' => 500]],
            ['shipping_settlements', ['reference' => 'DEMO-SETTLEMENT-2026-09', 'status' => 'reconciled']],
            ['shipping_settlement_items', ['order_number' => 'DEMO-2026-0001', 'status' => 'matched']],
            ['shipping_webhook_events', ['event_id' => 'seed-shipping-event-0001', 'status' => 'processed']],
            ['order_returns', ['reason' => 'demo_partial_return', 'status' => 'completed']],
            ['order_return_items', ['quantity' => 1, 'unit_price' => 4500]],
            ['social_connections', ['provider_account_id' => 'demo-social-account-001', 'is_active' => false]],
            ['social_conversations', ['provider_conversation_id' => 'demo-conversation-001']],
            ['social_messages', ['provider_message_id' => 'demo-outbound-message-001']],
            ['social_interactions', ['provider_interaction_id' => 'demo-comment-001']],
            ['social_webhook_events', ['provider_event_id' => 'demo-webhook-event-001', 'status' => 'processed']],
            ['social_message_templates', ['name' => 'Demo product availability reply', 'is_active' => false]],
            ['social_automation_rules', ['name' => 'Demo inquiry rule (disabled)', 'is_active' => false]],
            ['social_automation_executions', ['idempotency_key' => 'seed-social-execution-demo-001', 'status' => 'completed']],
            ['landing_pages', ['slug' => 'demo-sustainable-home', 'status' => 'draft']],
            ['landing_page_leads', ['dedupe_key' => 'seed-demo-lead-001', 'status' => 'new']],
            ['landing_page_events', ['dedupe_key' => 'seed-demo-view-001', 'event_type' => 'view']],
            ['ai_generations', ['kind' => 'landing_page_copy', 'model' => 'mock-fixture', 'status' => 'completed']],
            ['inventory_movements', ['reason' => 'demo_initial_stock', 'quantity' => 48]],
            ['inventory_movements', ['reason' => 'demo_initial_stock', 'note' => 'Initial stock for recycled-cotton-tote.', 'quantity' => 42]],
            ['audit_logs', ['action' => 'demo.seeded', 'target_type' => 'DatabaseSeeder']],
            ['outbox_events', ['deduplication_key' => 'seed-order-delivered-demo-001', 'status' => 'dispatched']],
            ['operational_alerts', ['type' => 'demo_fixture_review', 'status' => 'resolved']],
            ['customer_notifications', ['type' => 'demo_order_delivered']],
        ] as [$table, $criteria]) {
            $this->assertDatabaseHas($table, $criteria);
        }

        $this->assertDatabaseCount('product_variant_attribute_values', 4);
        $this->assertDatabaseCount('customer_carts', 1);
        $this->assertDatabaseCount('customer_wishlists', 1);
        $this->assertDatabaseCount('customer_preferences', 1);
        $this->assertDatabaseCount('operational_alert_notifications', 1);
        $this->assertTrue(DB::table('inventory_items')
            ->join('products', 'products.id', '=', 'inventory_items.product_id')
            ->where('products.slug', 'solar-garden-lantern')
            ->whereNull('inventory_items.variant_id')
            ->where('inventory_items.on_hand', 4)
            ->where('inventory_items.reserved', 1)
            ->exists());
        $this->assertTrue(DB::table('inventory_items')
            ->join('products', 'products.id', '=', 'inventory_items.product_id')
            ->join('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
            ->where('products.slug', 'glass-storage-set')
            ->where('product_variants.sku', 'ECO-STORAGE-CLEAR')
            ->where('inventory_items.on_hand', 14)
            ->where('inventory_items.reserved', 2)
            ->exists());

        $tables = [
            'users', 'roles', 'permissions', 'settings', 'brands', 'categories', 'attributes', 'attribute_values',
            'products', 'product_variants', 'product_variant_attribute_values', 'product_media', 'product_reviews',
            'customer_addresses', 'customer_carts', 'customer_cart_items', 'customer_wishlists', 'customer_preferences',
            'coupons', 'tax_rules', 'customer_orders', 'customer_order_items', 'coupon_usages',
            'order_activities', 'order_reviews', 'payments', 'payment_operations', 'payment_webhook_events',
            'provider_circuit_breakers', 'shipping_methods', 'shipping_providers', 'shipping_pricing_plans',
            'shipping_pricing_rules', 'shipping_fee_options', 'shipments', 'shipment_events', 'shipment_operations',
            'shipment_pricing_snapshots', 'shipping_settlements', 'shipping_settlement_items', 'shipping_webhook_events',
            'order_returns', 'order_return_items', 'social_connections', 'social_conversations', 'social_messages',
            'social_interactions', 'social_webhook_events', 'social_message_templates', 'social_automation_rules',
            'social_automation_executions', 'landing_pages', 'landing_page_leads', 'landing_page_events',
            'ai_generations', 'inventory_items', 'inventory_movements', 'audit_logs', 'outbox_events',
            'order_monitoring_settings', 'operational_alerts', 'operational_alert_notifications', 'customer_notifications',
        ];
        $countsAfterFirstRun = array_map(
            static fn (string $table): int => DB::table($table)->count(),
            $tables
        );

        $this->seed();

        $countsAfterSecondRun = array_map(
            static fn (string $table): int => DB::table($table)->count(),
            $tables
        );

        $this->assertSame($countsAfterFirstRun, $countsAfterSecondRun);
    }

    public function test_production_seeding_does_not_create_demo_credentials(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'customer@example.com']);
    }
}
