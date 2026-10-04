<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $order = DB::table('customer_orders')->where('order_number', 'DEMO-2026-0001')->first();

        if ($order === null) {
            return;
        }

        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@example.com')->value('id');
        $address = json_decode((string) $order->shipping_address, true, 512, JSON_THROW_ON_ERROR);

        DB::table('shipping_methods')->updateOrInsert(
            ['code' => 'demo-standard'],
            [
                'name' => 'Demo Standard Delivery',
                'carrier' => 'Demo Carrier',
                'base_fee' => 500,
                'currency' => 'EGP',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $methodId = DB::table('shipping_methods')->where('code', 'demo-standard')->value('id');

        DB::table('shipping_providers')->updateOrInsert(
            ['code' => 'demo-carrier'],
            [
                'name' => 'Demo Carrier (disabled)',
                'is_active' => false,
                'metadata' => json_encode(['fixture' => true, 'external_calls' => false], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $providerId = DB::table('shipping_providers')->where('code', 'demo-carrier')->value('id');

        DB::table('shipping_pricing_plans')->updateOrInsert(
            ['shipping_provider_id' => $providerId, 'name' => 'Demo Cairo Rates'],
            [
                'pricing_method' => 'weight',
                'currency' => 'EGP',
                'is_active' => false,
                'effective_from' => $now->copy()->subMonth(),
                'effective_until' => $now->copy()->addMonth(),
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $planId = DB::table('shipping_pricing_plans')->where('shipping_provider_id', $providerId)->where('name', 'Demo Cairo Rates')->value('id');

        DB::table('shipping_pricing_rules')->updateOrInsert(
            ['shipping_pricing_plan_id' => $planId, 'rule_type' => 'base', 'zone_code' => 'CAIRO'],
            [
                'min_weight' => 0,
                'max_weight' => 10,
                'min_quantity' => 1,
                'max_quantity' => 20,
                'base_amount' => 500,
                'additional_unit_amount' => 0,
                'sort_order' => 0,
                'is_active' => true,
                'conditions' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('shipping_fee_options')->updateOrInsert(
            ['shipping_pricing_plan_id' => $planId, 'code' => 'demo-delivery'],
            [
                'name' => 'Demo Delivery Fee',
                'fee_type' => 'fixed',
                'amount' => 0,
                'applies_to' => 'delivered',
                'trigger_conditions' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('shipments')->updateOrInsert(
            ['order_id' => $order->id],
            [
                'user_id' => $order->user_id,
                'shipping_method_id' => $methodId,
                'method_code' => 'demo-standard',
                'provider_code' => 'demo-carrier',
                'tracking_number' => 'DEMO-TRK-0001',
                'fee' => 500,
                'currency' => 'EGP',
                'status' => 'delivered',
                'creation_status' => 'created',
                'creation_error' => null,
                'created_at_provider' => $now->copy()->subDay(),
                'address_snapshot' => json_encode($address, JSON_THROW_ON_ERROR),
                'idempotency_key' => 'seed-shipment-demo-001',
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'weight' => 0.700,
                'item_quantity' => 2,
                'zone_code' => 'CAIRO',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $shipmentId = DB::table('shipments')->where('order_id', $order->id)->value('id');

        foreach ([
            ['from_status' => null, 'to_status' => 'pending', 'note' => 'Demo shipment created.'],
            ['from_status' => 'shipped', 'to_status' => 'delivered', 'note' => 'Demo shipment delivered.'],
        ] as $event) {
            DB::table('shipment_events')->updateOrInsert(
                ['shipment_id' => $shipmentId, 'to_status' => $event['to_status']],
                [
                    'from_status' => $event['from_status'],
                    'actor_id' => $adminId,
                    'note' => $event['note'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('shipment_operations')->updateOrInsert(
            ['shipment_id' => $shipmentId, 'operation' => 'create', 'idempotency_key' => 'seed-shipment-create-demo-001'],
            [
                'status' => 'completed',
                'provider_reference' => 'DEMO-TRK-0001',
                'attempt_count' => 1,
                'request_payload' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'response_payload' => json_encode(['tracking_number' => 'DEMO-TRK-0001'], JSON_THROW_ON_ERROR),
                'last_error' => null,
                'next_retry_at' => null,
                'lease_token' => null,
                'lease_expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('shipment_pricing_snapshots')->updateOrInsert(
            ['shipment_id' => $shipmentId],
            [
                'shipping_provider_id' => $providerId,
                'shipping_pricing_plan_id' => $planId,
                'pricing_method' => 'weight',
                'weight' => 0.700,
                'item_quantity' => 2,
                'zone_code' => 'CAIRO',
                'currency' => 'EGP',
                'base_amount' => 500,
                'applied_fees' => json_encode([['code' => 'demo-delivery', 'amount' => 0]], JSON_THROW_ON_ERROR),
                'total_expected_cost' => 500,
                'calculation_inputs' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $settlementReference = 'DEMO-SETTLEMENT-2026-09';
        DB::table('shipping_settlements')->updateOrInsert(
            ['reference' => $settlementReference],
            [
                'shipping_provider_id' => $providerId,
                'period_from' => $now->toDateString(),
                'period_to' => $now->toDateString(),
                'status' => 'reconciled',
                'currency' => 'EGP',
                'shipments_count' => 1,
                'total_rows' => 1,
                'matched_rows' => 1,
                'mismatched_rows' => 0,
                'missing_orders' => 0,
                'duplicate_rows' => 0,
                'invalid_rows' => 0,
                'expected_total' => 500,
                'actual_total' => 500,
                'difference_total' => 0,
                'expected_collection' => 9734,
                'actual_collection' => 9734,
                'collection_difference' => 0,
                'expected_shipping' => 500,
                'actual_shipping' => 500,
                'shipping_difference' => 0,
                'expected_return_fee' => 0,
                'actual_return_fee' => 0,
                'return_difference' => 0,
                'expected_customer_refund' => 450,
                'actual_customer_refund' => 450,
                'customer_refund_difference' => 0,
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $settlementId = DB::table('shipping_settlements')->where('reference', $settlementReference)->value('id');

        DB::table('shipping_settlement_items')->updateOrInsert(
            ['shipping_settlement_id' => $settlementId, 'shipment_id' => $shipmentId],
            [
                'order_number' => $order->order_number,
                'expected_total' => 500,
                'actual_total' => 500,
                'difference' => 0,
                'status' => 'matched',
                'expected_charges' => json_encode(['shipping' => 500], JSON_THROW_ON_ERROR),
                'actual_charges' => json_encode(['shipping' => 500], JSON_THROW_ON_ERROR),
                'expected_order_amount' => 9734,
                'actual_order_amount' => 9734,
                'order_amount_difference' => 0,
                'expected_collection' => 9734,
                'actual_collection' => 9734,
                'collection_difference' => 0,
                'expected_shipping_cost' => 500,
                'actual_shipping_cost' => 500,
                'shipping_difference' => 0,
                'expected_return_fee' => 0,
                'actual_return_fee' => 0,
                'return_difference' => 0,
                'expected_customer_refund' => 450,
                'actual_customer_refund' => 450,
                'customer_refund_difference' => 0,
                'requested_customer_refund' => 450,
                'confirmed_customer_refund' => 450,
                'refund_reconciliation_difference' => 0,
                'refund_reconciliation_status' => 'matched',
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('shipping_webhook_events')->updateOrInsert(
            ['provider' => 'demo-carrier', 'event_id' => 'seed-shipping-event-0001'],
            [
                'event_type' => 'shipment.delivered',
                'shipment_reference' => 'DEMO-TRK-0001',
                'status' => 'processed',
                'payload' => json_encode(['tracking_number' => 'DEMO-TRK-0001', 'status' => 'delivered'], JSON_THROW_ON_ERROR),
                'processing_error' => null,
                'processed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
