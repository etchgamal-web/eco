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

        $this->getJson('/api/v1/content')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'published-guide')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.last_page', 1);
        $this->getJson('/api/v1/content/draft-article')->assertNotFound();
    }

    public function test_public_content_api_paginates_published_items_and_returns_the_last_page(): void
    {
        $publishedAt = now();
        foreach (range(1, 21) as $index) {
            ContentItem::query()->create([
                'type' => 'article',
                'title' => "Article {$index}",
                'slug' => "article-{$index}",
                'status' => 'published',
                'published_at' => $publishedAt,
            ]);
        }
        ContentItem::query()->create([
            'type' => 'article',
            'title' => 'Unpublished article',
            'slug' => 'unpublished-article',
            'status' => 'draft',
        ]);

        $this->getJson('/api/v1/content?type=article&page=1&per_page=20')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.last_page', 2);

        $lastPage = $this->getJson('/api/v1/content?type=article&page=2&per_page=20')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'article-1')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.last_page', 2);

        $this->assertNotContains('unpublished-article', array_column($lastPage->json('data'), 'slug'));
    }

    public function test_public_content_api_returns_valid_metadata_when_no_items_match(): void
    {
        $this->getJson('/api/v1/content?type=guide&page=1&per_page=20')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.last_page', 1);
    }

    public function test_public_content_api_rejects_invalid_pagination_values(): void
    {
        $this->getJson('/api/v1/content?page=0&per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'per_page']);
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
