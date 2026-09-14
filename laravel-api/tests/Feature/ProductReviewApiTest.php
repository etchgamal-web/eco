<?php
namespace Tests\Feature;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
final class ProductReviewApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_verified_customer_can_submit_and_admin_can_moderate_review(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->user('customer'); $admin = $this->user('admin');
        $product = Product::query()->create(['name' => 'Reviewed Product', 'slug' => 'reviewed-product', 'type' => 'simple', 'status' => 'active', 'price' => 100]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 100, 'currency' => 'EGP']);
        $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => 100]);
        $review = $this->actingAs($customer)->postJson('/api/v1/products/'.$product->id.'/reviews', ['rating' => 5, 'title' => 'Excellent', 'body' => 'Great product.'])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');
        $this->actingAs($admin)->patchJson('/api/v1/reviews/'.$review.'/moderate', ['status' => 'approved'])->assertOk();
        $this->actingAs($customer)->getJson('/api/v1/products/'.$product->id.'/reviews')->assertOk()->assertJsonPath('meta.count', 1)->assertJsonPath('meta.average_rating', 5);
        $this->assertDatabaseHas('product_reviews', ['id' => $review, 'verified_purchase' => true, 'status' => 'approved']);
        $this->actingAs($customer)->postJson('/api/v1/products/'.$product->id.'/reviews', ['rating' => 4])->assertStatus(409);
    }
    public function test_customer_cannot_review_without_a_delivered_purchase(): void
    {
        $this->seed(RbacSeeder::class); $customer = $this->user('customer');
        $product = Product::query()->create(['name' => 'Unpurchased Product', 'slug' => 'unpurchased-product', 'type' => 'simple', 'status' => 'active', 'price' => 100]);
        $this->actingAs($customer)->postJson('/api/v1/products/'.$product->id.'/reviews', ['rating' => 5])->assertStatus(409);
    }
    private function user(string $role): User { $user = User::factory()->create(); $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail()); return $user; }
}
