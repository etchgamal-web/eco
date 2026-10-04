<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationsFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $order = DB::table('customer_orders')->where('order_number', 'DEMO-2026-0001')->first();
        $adminId = DB::table('users')->where('email', 'admin@example.com')->value('id');
        $customerId = DB::table('users')->where('email', 'customer@example.com')->value('id');
        $inventoryItem = DB::table('inventory_items')
            ->join('products', 'products.id', '=', 'inventory_items.product_id')
            ->where('products.slug', 'reusable-bamboo-bottle')
            ->select('inventory_items.*')
            ->first();

        if ($order === null || $adminId === null || $customerId === null || $inventoryItem === null) {
            return;
        }

        $now = now();
        DB::table('inventory_items')->where('id', $inventoryItem->id)->update([
            'on_hand' => 49,
            'reserved' => 3,
            'updated_at' => $now,
        ]);

        foreach ([
            ['reason' => 'demo_initial_stock', 'quantity' => 48, 'on_hand_after' => 48, 'note' => 'Initial local fixture stock.'],
            ['reason' => 'demo_return_restock', 'quantity' => 1, 'on_hand_after' => 49, 'note' => 'Restock for DEMO-2026-0001.'],
        ] as $movement) {
            DB::table('inventory_movements')->updateOrInsert(
                ['inventory_item_id' => $inventoryItem->id, 'reason' => $movement['reason'], 'note' => $movement['note']],
                [
                    'actor_id' => $adminId,
                    'quantity' => $movement['quantity'],
                    'on_hand_after' => $movement['on_hand_after'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('audit_logs')->updateOrInsert(
            ['actor_id' => $adminId, 'action' => 'demo.seeded', 'target_type' => 'DatabaseSeeder', 'target_id' => 1],
            [
                'metadata' => json_encode(['fixture' => true, 'environment' => app()->environment()], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('outbox_events')->updateOrInsert(
            ['deduplication_key' => 'seed-order-delivered-demo-001'],
            [
                'aggregate_type' => 'order',
                'aggregate_id' => $order->id,
                'event_type' => 'order.delivered',
                'status' => 'dispatched',
                'attempt_count' => 1,
                'payload' => json_encode(['order_number' => $order->order_number, 'fixture' => true], JSON_THROW_ON_ERROR),
                'last_error' => null,
                'next_attempt_at' => null,
                'lease_until' => null,
                'claim_token' => null,
                'dispatched_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        foreach ([
            ['rule_type' => 'review_overdue', 'days' => 1],
            ['rule_type' => 'processing_overdue', 'days' => 2],
            ['rule_type' => 'shipment_no_update', 'days' => 5],
            ['rule_type' => 'delivery_overdue', 'days' => 2],
            ['rule_type' => 'settlement_missing', 'days' => 3],
            ['rule_type' => 'return_settlement_missing', 'days' => 3],
        ] as $setting) {
            DB::table('order_monitoring_settings')->updateOrInsert(
                ['rule_type' => $setting['rule_type']],
                [
                    'days' => $setting['days'],
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('operational_alerts')->updateOrInsert(
            ['order_id' => $order->id, 'type' => 'demo_fixture_review'],
            [
                'severity' => 'low',
                'status' => 'resolved',
                'detected_at' => $now->copy()->subDay(),
                'acknowledged_at' => $now->copy()->subHours(12),
                'resolved_at' => $now->copy()->subHours(11),
                'acknowledged_by' => $adminId,
                'resolved_by' => $adminId,
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $alertId = DB::table('operational_alerts')->where('order_id', $order->id)->where('type', 'demo_fixture_review')->value('id');

        DB::table('operational_alert_notifications')->updateOrInsert(
            ['operational_alert_id' => $alertId, 'user_id' => $adminId],
            ['sent_at' => $now]
        );

        DB::table('customer_notifications')->updateOrInsert(
            ['user_id' => $customerId, 'type' => 'demo_order_delivered'],
            [
                'title' => 'Your demo order was delivered',
                'body' => 'This local fixture is available for notification API tests.',
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
