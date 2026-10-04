<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PromotionTaxFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('coupons')->updateOrInsert(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percent',
                'value' => 10,
                'minimum_order_amount' => 0,
                'usage_limit' => 100,
                'per_user_limit' => 1,
                'starts_at' => $now->copy()->subMonth(),
                'ends_at' => $now->copy()->addMonth(),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tax_rules')->updateOrInsert(
            ['name' => 'Egypt VAT Demo', 'country' => 'EG', 'state' => 'Cairo'],
            [
                'rate' => 14.0000,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
