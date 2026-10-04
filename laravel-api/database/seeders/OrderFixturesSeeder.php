<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $customerId = DB::table('users')->where('email', 'customer@example.com')->value('id');
        $adminId = DB::table('users')->where('email', 'admin@example.com')->value('id');
        $product = DB::table('products')->where('slug', 'reusable-bamboo-bottle')->first();
        $variant = DB::table('product_variants')->where('sku', 'ECO-BOTTLE-WHITE-BAMBOO')->first();
        $couponId = DB::table('coupons')->where('code', 'WELCOME10')->value('id');
        $taxRuleId = DB::table('tax_rules')->where('name', 'Egypt VAT Demo')->value('id');
        $address = DB::table('customer_addresses')->where('user_id', $customerId)->where('label', 'home')->first();

        if ($customerId === null || $adminId === null || $product === null || $variant === null || $address === null) {
            return;
        }

        $now = now();
        $orderNumber = 'DEMO-2026-0001';
        $shippingAddress = [
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
        ];

        DB::table('customer_orders')->updateOrInsert(
            ['order_number' => $orderNumber],
            [
                'user_id' => $customerId,
                'status' => 'delivered',
                'subtotal_amount' => 9000,
                'discount_amount' => 900,
                'coupon_code' => 'WELCOME10',
                'tax_amount' => 1134,
                'tax_rate' => 14.0000,
                'tax_rule_id' => $taxRuleId,
                'shipping_amount' => 500,
                'shipping_cost' => 500,
                'shipping_subsidy' => 0,
                'total_amount' => 9734,
                'currency' => 'EGP',
                'shipping_address' => json_encode($shippingAddress, JSON_THROW_ON_ERROR),
                'idempotency_key' => 'seed-order-demo-001',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $orderId = DB::table('customer_orders')->where('order_number', $orderNumber)->value('id');

        DB::table('customer_order_items')->updateOrInsert(
            ['order_id' => $orderId, 'sku' => 'ECO-BOTTLE-WHITE-BAMBOO'],
            [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'name' => $product->name,
                'quantity' => 2,
                'unit_price' => 4500,
                'discount_amount' => 900,
                'tax_amount' => 1134,
                'total_amount' => 9234,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if ($couponId !== null) {
            DB::table('coupon_usages')->updateOrInsert(
                ['coupon_id' => $couponId, 'user_id' => $customerId, 'order_id' => $orderId],
                [
                    'discount_amount' => 900,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        foreach ([
            ['event' => 'order_created', 'from_status' => null, 'to_status' => 'pending', 'occurred_at' => $now->copy()->subDays(3)],
            ['event' => 'payment_confirmed', 'from_status' => 'pending', 'to_status' => 'confirmed', 'occurred_at' => $now->copy()->subDays(3)->addMinutes(2)],
            ['event' => 'order_delivered', 'from_status' => 'shipped', 'to_status' => 'delivered', 'occurred_at' => $now->copy()->subDay()],
        ] as $activity) {
            DB::table('order_activities')->updateOrInsert(
                ['order_id' => $orderId, 'event' => $activity['event']],
                [
                    'actor_id' => $adminId,
                    'source' => 'demo',
                    'from_status' => $activity['from_status'],
                    'to_status' => $activity['to_status'],
                    'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                    'occurred_at' => $activity['occurred_at'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('order_reviews')->updateOrInsert(
            ['order_id' => $orderId],
            [
                'reviewer_id' => $adminId,
                'started_at' => $now->copy()->subDays(3),
                'contacted_at' => $now->copy()->subDays(3)->addMinute(),
                'contact_result' => 'confirmed',
                'notes' => 'Seeded order-review record for workflow tests.',
                'confirmed_at' => $now->copy()->subDays(3)->addMinutes(2),
                'confirmed_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
