<?php
namespace Tests\Feature;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
final class ProductMediaApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_upload_list_reorder_and_delete_product_media(): void
    {
        Storage::fake('public');
        $this->seed(RbacSeeder::class);
        $admin = $this->admin();
        $product = Product::query()->create(['name' => 'Media Product', 'slug' => 'media-product', 'type' => 'simple', 'status' => 'active', 'price' => 100]);
        $first = $this->actingAs($admin)->post('/api/v1/products/'.$product->id.'/media', ['file' => $this->png('first.png')])->assertCreated()->json('data.id');
        $second = $this->actingAs($admin)->post('/api/v1/products/'.$product->id.'/media', ['file' => $this->png('second.png')])->assertCreated()->json('data.id');
        $this->actingAs($admin)->getJson('/api/v1/products/'.$product->id.'/media')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($admin)->patchJson('/api/v1/products/'.$product->id.'/media/'.$second.'/order', ['sort_order' => 0])->assertOk()->assertJsonPath('data.sort_order', 0);
        $this->actingAs($admin)->deleteJson('/api/v1/products/'.$product->id.'/media/'.$first)->assertNoContent();
        $this->assertDatabaseCount('product_media', 1);
    }
    public function test_media_validation_rejects_non_images_and_customers_cannot_upload(): void
    {
        Storage::fake('public');
        $this->seed(RbacSeeder::class);
        $product = Product::query()->create(['name' => 'Media Product', 'slug' => 'media-product', 'type' => 'simple', 'status' => 'active', 'price' => 100]);
        $this->actingAs($this->admin())->post('/api/v1/products/'.$product->id.'/media', ['file' => UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream')])->assertUnprocessable();
        $customer = User::factory()->create();
        $customer->roles()->attach(Role::query()->where('slug', 'customer')->firstOrFail());
        $this->actingAs($customer)->getJson('/api/v1/products/'.$product->id.'/media')->assertForbidden();
    }
    private function admin(): User { $user = User::factory()->create(); $user->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail()); return $user; }
    private function png(string $name): UploadedFile { return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')); }
}
