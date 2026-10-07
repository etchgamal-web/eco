<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Models\Product;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicCatalogFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_public_product_list_returns_active_products_only(): void
    {
        Product::query()->create(['name' => 'Active', 'slug' => 'active-product', 'type' => 'simple', 'status' => 'active']);
        Product::query()->create(['name' => 'Draft', 'slug' => 'draft-product', 'type' => 'simple', 'status' => 'draft']);
        Product::query()->create(['name' => 'Inactive', 'slug' => 'inactive-product', 'type' => 'simple', 'status' => 'inactive']);

        $this->getJson('/api/v1/products')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'active-product');
    }

    public function test_public_product_detail_and_variants_hide_inactive_products_and_variants(): void
    {
        Product::query()->create(['name' => 'Hidden', 'slug' => 'hidden-product', 'type' => 'simple', 'status' => 'inactive']);
        $this->getJson('/api/v1/products/hidden-product')->assertNotFound();

        $active = Product::query()->create(['name' => 'Visible', 'slug' => 'visible-product', 'type' => 'variable', 'status' => 'active']);
        $active->variants()->createMany([
            ['sku' => 'ACTIVE-SKU', 'price' => 100, 'status' => 'active', 'combination_hash' => hash('sha256', 'active')],
            ['sku' => 'INACTIVE-SKU', 'price' => 100, 'status' => 'inactive', 'combination_hash' => hash('sha256', 'inactive')],
        ]);
        $this->getJson("/api/v1/products/{$active->id}/variants")->assertOk()->assertJsonCount(1, 'data');
    }
}
