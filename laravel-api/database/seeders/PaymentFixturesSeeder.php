<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $order = DB::table('customer_orders')->where('order_number', 'DEMO-2026-0001')->first();

        if ($order === null) {
            return;
        }

        $now = now();
        $paymentReference = 'DEMO-PAY-0001';
        $paymentKey = 'seed-payment-demo-001';

        DB::table('payments')->updateOrInsert(
            ['idempotency_key' => $paymentKey],
            [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'method' => 'cash_on_delivery',
                'provider_reference' => $paymentReference,
                'amount' => 9734,
                'currency' => 'EGP',
                'status' => 'partially_refunded',
                'metadata' => json_encode(['fixture' => true, 'provider' => 'demo'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $paymentId = DB::table('payments')->where('idempotency_key', $paymentKey)->value('id');

        DB::table('payment_operations')->updateOrInsert(
            ['payment_id' => $paymentId, 'operation' => 'confirm', 'idempotency_key' => 'seed-payment-confirm-demo-001'],
            [
                'return_id' => null,
                'status' => 'confirmed',
                'provider_reference' => $paymentReference,
                'requested_amount' => 9734,
                'confirmed_amount' => 9734,
                'attempt_count' => 1,
                'request_payload' => json_encode(['amount' => 9734, 'currency' => 'EGP'], JSON_THROW_ON_ERROR),
                'response_payload' => json_encode(['status' => 'confirmed', 'fixture' => true], JSON_THROW_ON_ERROR),
                'last_error' => null,
                'next_retry_at' => null,
                'last_reconciliation_at' => null,
                'next_reconciliation_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('payment_webhook_events')->updateOrInsert(
            ['provider' => 'demo', 'event_id' => 'seed-payment-event-0001'],
            [
                'event_type' => 'payment.captured',
                'status' => 'processed',
                'payment_reference' => $paymentReference,
                'payload' => json_encode(['reference' => $paymentReference, 'amount' => 9734, 'fixture' => true], JSON_THROW_ON_ERROR),
                'processing_error' => null,
                'processed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('provider_circuit_breakers')->updateOrInsert(
            ['provider' => 'demo-payment-provider'],
            [
                'failure_count' => 0,
                'opened_until' => null,
                'last_failure_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
