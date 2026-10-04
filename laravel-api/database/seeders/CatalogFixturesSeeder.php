<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $productId = DB::table('products')->where('slug', 'reusable-bamboo-bottle')->value('id');
        $variantId = DB::table('product_variants')->where('sku', 'ECO-BOTTLE-WHITE-BAMBOO')->value('id');
        $customerId = DB::table('users')->where('email', 'customer@example.com')->value('id');

        if ($productId === null || $variantId === null || $customerId === null) {
            return;
        }

        $now = now();

        DB::table('product_media')->updateOrInsert(
            ['product_id' => $productId, 'path' => 'demo/reusable-bamboo-bottle.jpg'],
            [
                'variant_id' => $variantId,
                'disk' => 'public',
                'url' => 'https://example.test/storage/demo/reusable-bamboo-bottle.jpg',
                'original_name' => 'reusable-bamboo-bottle.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 128000,
                'sort_order' => 0,
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('product_reviews')->updateOrInsert(
            ['product_id' => $productId, 'user_id' => $customerId],
            [
                'variant_id' => $variantId,
                'rating' => 5,
                'title' => 'Reliable everyday bottle',
                'body' => 'A reusable bottle fixture for local API and review tests.',
                'status' => 'approved',
                'verified_purchase' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
