<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Content\Infrastructure\Models\ContentItem;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_public_content_api_returns_published_content_only(): void
    {
        ContentItem::query()->create(['type' => 'guide', 'title' => 'Published guide', 'slug' => 'published-guide', 'body' => 'Answer', 'status' => 'published', 'published_at' => now()]);
        ContentItem::query()->create(['type' => 'article', 'title' => 'Draft article', 'slug' => 'draft-article', 'status' => 'draft']);

        $this->getJson('/api/v1/content')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'published-guide');
        $this->getJson('/api/v1/content/draft-article')->assertNotFound();
    }

    public function test_content_can_be_created_published_and_related_to_products(): void
    {
        $owner = $this->userWithRole('owner');
        $product = Product::query()->create(['name' => 'Compared product', 'slug' => 'compared-product', 'type' => 'simple', 'status' => 'active']);
        $response = $this->actingAs($owner)->postJson('/api/v1/admin/content', [
            'type' => 'comparison', 'title' => 'Compare products', 'slug' => 'compare-products', 'body' => 'Facts', 'product_ids' => [$product->id],
        ])->assertCreated()->assertJsonPath('data.status', 'draft');
        $id = $response->json('data.id');

        $this->getJson("/api/v1/admin/content/{$id}")->assertOk()->assertJsonPath('data.products.0.id', $product->id);
        $this->actingAs($owner)->postJson("/api/v1/admin/content/{$id}/publish")->assertOk()->assertJsonPath('data.status', 'published');
        $this->getJson('/api/v1/content/compare-products')->assertOk()->assertJsonPath('data.products.0.id', $product->id);
    }

    public function test_content_management_requires_cms_permission(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'support_agent')->firstOrFail());
        $this->actingAs($user)->getJson('/api/v1/admin/content')->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());
        return $user;
    }
}
