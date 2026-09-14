<?php
namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->admin = $this->createAdminUser();
    }

    public function test_product_crud_lists_relations_and_returns_404(): void
    {
        $brand = $this->createBrand();
        $category = $this->createCategory();

        $create = $this->actingAs($this->admin)->postJson('/api/v1/products', [
            'name' => 'Phone', 'description' => 'A phone', 'type' => 'simple', 'status' => 'active',
            'brand_id' => $brand->id, 'category_id' => $category->id,
        ])->assertCreated()->assertJsonPath('data.slug', 'phone')->assertJsonPath('data.brand.id', $brand->id);
        $id = $create->json('data.id');

        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson("/api/v1/products/{$id}")->assertOk()->assertJsonPath('data.category.id', $category->id);
        $this->patchJson("/api/v1/products/{$id}", [
            'name' => 'Smart Phone', 'slug' => 'smart-phone', 'description' => null,
            'type' => 'simple', 'status' => 'inactive', 'brand_id' => null, 'category_id' => null,
        ])->assertOk()->assertJsonPath('data.slug', 'smart-phone')->assertJsonPath('data.status', 'inactive');
        $this->deleteJson("/api/v1/products/{$id}")->assertNoContent();
        $this->getJson("/api/v1/products/{$id}")->assertNotFound();
    }

    public function test_product_validation_and_duplicate_slug_conflicts(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/products', [
            'name' => 'Existing', 'slug' => 'same', 'type' => 'simple', 'status' => 'active',
        ])->assertCreated();

        $this->postJson('/api/v1/products', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'status']);
        $this->postJson('/api/v1/products', [
            'name' => 'Invalid', 'type' => 'bundle', 'status' => 'active', 'brand_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'brand_id']);
        $this->postJson('/api/v1/products', [
            'name' => 'Duplicate', 'slug' => 'same', 'type' => 'simple', 'status' => 'active',
        ])->assertConflict();
    }

    public function test_product_search_filters_and_pagination_are_validated(): void
    {
        Product::query()->create(['name' => 'Alpha Phone', 'slug' => 'alpha-phone', 'type' => 'simple', 'status' => 'active', 'price' => 100]);
        Product::query()->create(['name' => 'Beta Phone', 'slug' => 'beta-phone', 'type' => 'simple', 'status' => 'inactive', 'price' => 300]);

        $this->actingAs($this->admin)->getJson('/api/v1/products?search=Alpha&status=active&min_price=50&max_price=200&sort=price_desc&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.slug', 'alpha-phone')
            ->assertJsonPath('data.per_page', 1)
            ->assertJsonPath('data.total', 1);
        $this->actingAs($this->admin)->getJson('/api/v1/products?sort=invalid')->assertUnprocessable()->assertJsonValidationErrors(['sort']);
    }

    public function test_variant_crud_and_nested_404_boundaries(): void
    {
        [$product, $colorRed] = $this->variableProductAndValue();
        $create = $this->actingAs($this->admin)->postJson("/api/v1/products/{$product->id}/variants", $this->variantPayload('SKU-1', [$colorRed->id]))
            ->assertCreated()->assertJsonPath('data.sku', 'SKU-1');
        $variantId = $create->json('data.id');

        $this->getJson("/api/v1/products/{$product->id}/variants")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/products/{$product->id}/variants/{$variantId}")->assertOk()->assertJsonPath('data.id', $variantId);
        $this->patchJson("/api/v1/products/{$product->id}/variants/{$variantId}", $this->variantPayload('SKU-1-UPDATED', [$colorRed->id], 250))
            ->assertOk()->assertJsonPath('data.price', 250);

        $other = Product::query()->create(['name' => 'Other', 'slug' => 'other', 'type' => 'variable', 'status' => 'active']);
        $this->getJson("/api/v1/products/{$other->id}/variants/{$variantId}")->assertNotFound();
        $this->getJson('/api/v1/products/999/variants')->assertNotFound();
        $this->deleteJson("/api/v1/products/{$product->id}/variants/{$variantId}")->assertNoContent();
        $this->getJson("/api/v1/products/{$product->id}/variants/{$variantId}")->assertNotFound();
    }

    public function test_simple_and_variable_product_rules(): void
    {
        $attribute = Attribute::query()->create(['name' => 'Size']);
        $value = $attribute->values()->create(['value' => 'M']);
        $simple = Product::query()->create(['name' => 'Simple', 'slug' => 'simple', 'type' => 'simple', 'status' => 'active']);

        $this->actingAs($this->admin)->postJson("/api/v1/products/{$simple->id}/variants", $this->variantPayload('SIMPLE-SKU', [$value->id]))
            ->assertConflict()->assertJsonPath('message', 'A simple product cannot have variants.');

        $variable = Product::query()->create(['name' => 'Variable', 'slug' => 'variable', 'type' => 'variable', 'status' => 'active']);
        $this->postJson("/api/v1/products/{$variable->id}/variants", $this->variantPayload('VARIABLE-SKU', [$value->id]))->assertCreated();
        $this->patchJson("/api/v1/products/{$variable->id}", [
            'name' => 'Variable', 'type' => 'simple', 'status' => 'active',
        ])->assertConflict()->assertJsonPath('message', 'A product with variants cannot be changed to simple.');
    }

    public function test_duplicate_sku_and_duplicate_attribute_combinations_return_409(): void
    {
        [$product, $red] = $this->variableProductAndValue();
        $size = Attribute::query()->create(['name' => 'Size']);
        $small = $size->values()->create(['value' => 'Small']);
        $blue = $red->attribute->values()->create(['value' => 'Blue']);

        $this->actingAs($this->admin)->postJson("/api/v1/products/{$product->id}/variants", $this->variantPayload('SKU-1', [$red->id, $small->id]))->assertCreated();
        $this->postJson("/api/v1/products/{$product->id}/variants", $this->variantPayload('SKU-1', [$blue->id, $small->id]))
            ->assertConflict()->assertJsonPath('message', 'The SKU [SKU-1] is already in use.');
        $this->postJson("/api/v1/products/{$product->id}/variants", $this->variantPayload('SKU-2', [$small->id, $red->id]))
            ->assertConflict()->assertJsonPath('message', 'The variant attribute combination already exists.');
    }

    public function test_variant_validation_and_incorrect_attribute_relationships_are_rejected(): void
    {
        [$product, $red] = $this->variableProductAndValue();
        $blue = $red->attribute->values()->create(['value' => 'Blue']);

        $this->actingAs($this->admin)->postJson("/api/v1/products/{$product->id}/variants", [
            'sku' => '', 'price' => -1, 'status' => 'active', 'attribute_value_ids' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['sku', 'price', 'attribute_value_ids']);
        $this->postJson("/api/v1/products/{$product->id}/variants", $this->variantPayload('BAD-COMBO', [$red->id, $blue->id]))
            ->assertConflict()->assertJsonPath('message', 'The variant attribute values must exist and contain one value per attribute.');
        $this->postJson("/api/v1/products/{$product->id}/variants", $this->variantPayload('DUP-ID', [$red->id, $red->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['attribute_value_ids.1']);
        $this->postJson("/api/v1/products/{$product->id}/variants", $this->variantPayload('MISSING-ID', [999]))
            ->assertUnprocessable()->assertJsonValidationErrors(['attribute_value_ids.0']);
    }

    public function test_update_variant_detects_sku_and_combination_conflicts(): void
    {
        [$product, $red] = $this->variableProductAndValue();
        $blue = $red->attribute->values()->create(['value' => 'Blue']);
        $first = $this->createVariant($product, 'FIRST', [$red]);
        $second = $this->createVariant($product, 'SECOND', [$blue]);

        $this->actingAs($this->admin)->patchJson("/api/v1/products/{$product->id}/variants/{$second->id}", $this->variantPayload('FIRST', [$blue->id]))
            ->assertConflict();
        $this->patchJson("/api/v1/products/{$product->id}/variants/{$second->id}", $this->variantPayload('SECOND', [$red->id]))
            ->assertConflict();
        $this->patchJson("/api/v1/products/{$product->id}/variants/{$first->id}", $this->variantPayload('FIRST', [$red->id]))
            ->assertOk();
    }

    private function createBrand(): Brand
    {
        return Brand::query()->create(['name' => 'Acme', 'slug' => 'acme', 'status' => 'active']);
    }

    private function createCategory(): Category
    {
        return Category::query()->create(['name' => 'Phones', 'slug' => 'phones', 'is_active' => true]);
    }

    /** @return array{Product, AttributeValue} */
    private function variableProductAndValue(): array
    {
        $product = Product::query()->create(['name' => 'Variable', 'slug' => 'variable', 'type' => 'variable', 'status' => 'active']);
        $attribute = Attribute::query()->create(['name' => 'Color']);

        return [$product, $attribute->values()->create(['value' => 'Red'])];
    }

    /** @param array<int, int> $valueIds */
    private function variantPayload(string $sku, array $valueIds, int $price = 100): array
    {
        return [
            'sku' => $sku, 'price' => $price, 'compare_at_price' => null, 'weight' => 1.25,
            'status' => 'active', 'variant_data' => ['label' => $sku], 'attribute_value_ids' => $valueIds,
        ];
    }

    /** @param array<int, AttributeValue> $values */
    private function createVariant(Product $product, string $sku, array $values): ProductVariant
    {
        $ids = collect($values)->pluck('id')->sort()->values();
        $variant = $product->variants()->create([
            'sku' => $sku, 'price' => 100, 'status' => 'active',
            'combination_hash' => hash('sha256', $ids->implode(':')),
        ]);
        foreach ($values as $value) {
            $variant->attributeValues()->attach($value->id, ['attribute_id' => $value->attribute_id]);
        }

        return $variant;
    }
}
