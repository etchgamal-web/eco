<?php
namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private ProductVariant $variant;
    private Attribute $attribute;
    private AttributeValue $value;
    private Brand $brand;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->user = User::factory()->create();
        $this->brand = Brand::query()->create(['name' => 'Brand', 'slug' => 'brand', 'status' => 'active']);
        $this->category = Category::query()->create(['name' => 'Category', 'slug' => 'category', 'is_active' => true]);
        $this->product = Product::query()->create([
            'name' => 'Variable', 'slug' => 'variable', 'type' => 'variable', 'status' => 'active',
            'brand_id' => $this->brand->id, 'category_id' => $this->category->id,
        ]);
        $this->attribute = Attribute::query()->create(['name' => 'Color']);
        $this->value = $this->attribute->values()->create(['value' => 'Red']);
        $this->variant = $this->product->variants()->create([
            'sku' => 'AUTH-SKU', 'price' => 100, 'status' => 'active',
            'combination_hash' => hash('sha256', (string) $this->value->id),
        ]);
        $this->variant->attributeValues()->attach($this->value->id, ['attribute_id' => $this->attribute->id]);
    }

    public function test_every_endpoint_requires_authentication(): void
    {
        foreach ($this->endpointRequests() as [$method, $uri, $payload]) {
            $response = $this->request($method, $uri, $payload);
            if ($this->isPublicStorefront($method, $uri)) {
                $response->assertOk();
            } else {
                $response->assertUnauthorized();
            }
        }
    }

    public function test_every_endpoint_rejects_an_authenticated_user_without_permission(): void
    {
        $this->actingAs($this->user);

        foreach ($this->endpointRequests() as [$method, $uri, $payload]) {
            $response = $this->request($method, $uri, $payload);
            if ($this->isPublicStorefront($method, $uri)) {
                $response->assertOk();
            } else {
                $response->assertForbidden();
            }
        }
    }

    public function test_authenticated_user_with_permission_is_not_rejected_by_authorization(): void
    {
        $admin = Role::query()->where('slug', 'admin')->firstOrFail();
        $this->user->roles()->attach($admin);
        $this->actingAs($this->user);

        foreach ($this->endpointRequests() as [$method, $uri, $payload]) {
            $response = $this->request($method, $uri, $payload);

            self::assertNotSame(401, $response->status(), $method.' '.$uri.' was not authenticated.');
            self::assertNotSame(403, $response->status(), $method.' '.$uri.' was not authorized.');
        }
    }

    /** @return array<int, array{string, string, array<string, mixed>}> */
    private function endpointRequests(): array
    {
        $product = $this->product->id;
        $variant = $this->variant->id;
        $attribute = $this->attribute->id;
        $value = $this->value->id;
        $brand = $this->brand->id;
        $category = $this->category->id;

        return [
            ['GET', '/api/v1/products', []],
            ['POST', '/api/v1/products', []],
            ['GET', "/api/v1/products/{$product}", []],
            ['PATCH', "/api/v1/products/{$product}", []],
            ['DELETE', "/api/v1/products/{$product}", []],
            ['GET', "/api/v1/products/{$product}/variants", []],
            ['POST', "/api/v1/products/{$product}/variants", []],
            ['GET', "/api/v1/products/{$product}/variants/{$variant}", []],
            ['PATCH', "/api/v1/products/{$product}/variants/{$variant}", []],
            ['DELETE', "/api/v1/products/{$product}/variants/{$variant}", []],
            ['GET', '/api/v1/attributes', []],
            ['POST', '/api/v1/attributes', []],
            ['GET', "/api/v1/attributes/{$attribute}", []],
            ['PATCH', "/api/v1/attributes/{$attribute}", []],
            ['DELETE', "/api/v1/attributes/{$attribute}", []],
            ['GET', "/api/v1/attributes/{$attribute}/values", []],
            ['POST', "/api/v1/attributes/{$attribute}/values", []],
            ['GET', "/api/v1/attributes/{$attribute}/values/{$value}", []],
            ['PATCH', "/api/v1/attributes/{$attribute}/values/{$value}", []],
            ['DELETE', "/api/v1/attributes/{$attribute}/values/{$value}", []],
            ['GET', '/api/v1/brands', []],
            ['POST', '/api/v1/brands', []],
            ['GET', "/api/v1/brands/{$brand}", []],
            ['PATCH', "/api/v1/brands/{$brand}", []],
            ['DELETE', "/api/v1/brands/{$brand}", []],
            ['GET', '/api/v1/categories', []],
            ['POST', '/api/v1/categories', []],
            ['GET', "/api/v1/categories/{$category}", []],
            ['PATCH', "/api/v1/categories/{$category}", []],
            ['DELETE', "/api/v1/categories/{$category}", []],
            ['GET', '/api/v1/settings', []],
            ['GET', '/api/v1/settings/groups/store', []],
            ['GET', '/api/v1/settings/store.name', []],
            ['PUT', '/api/v1/settings/store.name', []],
            ['GET', '/api/v1/customer/profile', []],
            ['GET', '/api/v1/customer/addresses', []],
            ['GET', '/api/v1/customer/addresses/default', []],
            ['GET', '/api/v1/customer/orders', []],
            ['GET', '/api/v1/customer/cart', []],
            ['GET', '/api/v1/customer/wishlist', []],
            ['GET', '/api/v1/customer/preferences', []],
            ['GET', '/api/v1/customer/notifications', []],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function request(string $method, string $uri, array $payload): TestResponse
    {
        return $this->json($method, $uri, $payload);
    }

    private function isPublicStorefront(string $method, string $uri): bool
    {
        return $method === 'GET' && (bool) preg_match('#^/api/v1/products(?:/\d+|/\d+/variants(?:/\d+)?)?$#', $uri);
    }
}
