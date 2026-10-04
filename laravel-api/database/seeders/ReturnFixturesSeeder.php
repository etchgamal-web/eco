<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $order = DB::table('customer_orders')->where('order_number', 'DEMO-2026-0001')->first();

        if ($order === null) {
            return;
        }

        $paymentId = DB::table('payments')->where('idempotency_key', 'seed-payment-demo-001')->value('id');
        $shipmentId = DB::table('shipments')->where('order_id', $order->id)->value('id');
        $orderItem = DB::table('customer_order_items')->where('order_id', $order->id)->where('sku', 'ECO-BOTTLE-WHITE-BAMBOO')->first();

        if ($paymentId === null || $shipmentId === null || $orderItem === null) {
            return;
        }

        $now = now();

        DB::table('order_returns')->updateOrInsert(
            ['order_id' => $order->id, 'reason' => 'demo_partial_return'],
            [
                'shipment_id' => $shipmentId,
                'payment_id' => $paymentId,
                'user_id' => $order->user_id,
                'status' => 'completed',
                'notes' => 'Completed local fixture for return and refund tests.',
                'refund_amount' => 450,
                'actual_customer_refund' => 450,
                'return_shipping_fee' => 0,
                'received_at' => $now->copy()->subHours(4),
                'inspected_at' => $now->copy()->subHours(3),
                'inspection_notes' => 'Fixture item inspected and accepted.',
                'restocked_at' => $now->copy()->subHours(2),
                'restock_status' => 'completed',
                'refund_requested_at' => $now->copy()->subHours(2),
                'refund_status' => 'completed',
                'workflow_error' => null,
                'last_workflow_attempt_at' => $now->copy()->subHours(1),
                'completed_at' => $now->copy()->subHour(),
                'rejection_reason' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $returnId = DB::table('order_returns')->where('order_id', $order->id)->where('reason', 'demo_partial_return')->value('id');

        DB::table('order_return_items')->updateOrInsert(
            ['return_id' => $returnId, 'order_item_id' => $orderItem->id],
            [
                'product_id' => $orderItem->product_id,
                'variant_id' => $orderItem->variant_id,
                'quantity' => 1,
                'unit_price' => 4500,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('payment_operations')->updateOrInsert(
            ['payment_id' => $paymentId, 'operation' => 'refund', 'idempotency_key' => 'seed-payment-refund-demo-001'],
            [
                'return_id' => $returnId,
                'status' => 'refunded',
                'provider_reference' => 'DEMO-REFUND-0001',
                'requested_amount' => 450,
                'confirmed_amount' => 450,
                'attempt_count' => 1,
                'request_payload' => json_encode(['amount' => 450, 'currency' => 'EGP'], JSON_THROW_ON_ERROR),
                'response_payload' => json_encode(['status' => 'refunded', 'fixture' => true], JSON_THROW_ON_ERROR),
                'last_error' => null,
                'next_retry_at' => null,
                'last_reconciliation_at' => $now,
                'next_reconciliation_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
