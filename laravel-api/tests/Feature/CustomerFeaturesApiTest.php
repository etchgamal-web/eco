<?php
namespace Tests\Feature;
use App\Models\CustomerNotification;use App\Models\CustomerOrder;use App\Models\Product;use App\Models\Role;use App\Models\User;use Database\Seeders\RbacSeeder;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;
final class CustomerFeaturesApiTest extends TestCase {
 use RefreshDatabase; private User $customer; private Product $product;
 protected function setUp():void{parent::setUp();$this->seed(RbacSeeder::class);$this->customer=User::factory()->create();$this->customer->roles()->attach(Role::query()->where('slug','customer')->firstOrFail());$this->product=Product::query()->create(['name'=>'Phone','slug'=>'phone','type'=>'simple','status'=>'active']);}
 public function test_addresses_default_address_cart_wishlist_preferences_and_notifications():void{
  $this->actingAs($this->customer)->postJson('/api/v1/customer/addresses',['recipient_name'=>'A','phone'=>'010','address_line1'=>'Street 1','city'=>'Cairo','country'=>'EG','is_default'=>true])->assertCreated();
  $second=$this->actingAs($this->customer)->postJson('/api/v1/customer/addresses',['recipient_name'=>'B','phone'=>'011','address_line1'=>'Street 2','city'=>'Cairo','country'=>'EG','is_default'=>true])->assertCreated();
  $this->assertDatabaseHas('customer_addresses',['user_id'=>$this->customer->id,'is_default'=>0]);$this->actingAs($this->customer)->getJson('/api/v1/customer/addresses/default')->assertOk()->assertJsonPath('data.recipient_name','B');$secondId=$second->json('data.id');$this->actingAs($this->customer)->deleteJson("/api/v1/customer/addresses/{$secondId}")->assertNoContent();
  $this->actingAs($this->customer)->postJson('/api/v1/customer/cart/items',['product_id'=>$this->product->id,'quantity'=>2])->assertCreated();$this->actingAs($this->customer)->getJson('/api/v1/customer/cart')->assertOk()->assertJsonPath('data.items.0.quantity',2);
  $this->actingAs($this->customer)->postJson('/api/v1/customer/wishlist',['product_id'=>$this->product->id])->assertCreated();$this->actingAs($this->customer)->getJson('/api/v1/customer/wishlist')->assertOk()->assertJsonCount(1,'data');
  $this->actingAs($this->customer)->putJson('/api/v1/customer/preferences',['data'=>['locale'=>'ar','marketing'=>false]])->assertOk();$this->actingAs($this->customer)->getJson('/api/v1/customer/preferences')->assertOk()->assertJsonPath('data.data.locale','ar');
  CustomerNotification::query()->create(['user_id'=>$this->customer->id,'type'=>'order','title'=>'Order','body'=>'Ready']);$this->actingAs($this->customer)->getJson('/api/v1/customer/notifications')->assertOk()->assertJsonCount(1,'data');
 }
 public function test_customer_cannot_read_modify_or_delete_another_customers_data():void{
  $other=User::factory()->create();
  $otherAddress=
   \App\Models\CustomerAddress::query()->create(['user_id'=>$other->id,'recipient_name'=>'Other','phone'=>'012','address_line1'=>'Other Street','city'=>'Cairo','country'=>'EG','is_default'=>true]);
  \App\Models\CustomerCart::query()->create(['user_id'=>$other->id]);
  \App\Models\CustomerWishlist::query()->create(['user_id'=>$other->id,'product_id'=>$this->product->id]);
  \App\Models\CustomerPreference::query()->create(['user_id'=>$other->id,'data'=>['secret'=>true]]);
  $notification=CustomerNotification::query()->create(['user_id'=>$other->id,'type'=>'private','title'=>'Private']);
  $this->actingAs($this->customer)->getJson('/api/v1/customer/addresses/default')->assertNotFound();
  $this->actingAs($this->customer)->putJson("/api/v1/customer/addresses/{$otherAddress->id}",['recipient_name'=>'Hijacked','phone'=>'013','address_line1'=>'No','city'=>'Cairo','country'=>'EG'])->assertNotFound();
  $this->actingAs($this->customer)->deleteJson("/api/v1/customer/addresses/{$otherAddress->id}")->assertNotFound();
  $this->actingAs($this->customer)->getJson('/api/v1/customer/cart')->assertOk()->assertJsonMissing(['user_id'=>$other->id]);
  $this->actingAs($this->customer)->getJson('/api/v1/customer/wishlist')->assertOk()->assertJsonCount(0,'data');
  $this->actingAs($this->customer)->getJson('/api/v1/customer/preferences')->assertOk()->assertJsonMissing(['secret'=>true]);
  $this->actingAs($this->customer)->patchJson("/api/v1/customer/notifications/{$notification->id}/read")->assertNotFound();
 }

 public function test_customer_can_list_only_own_orders_and_guests_are_rejected():void{
  $this->getJson('/api/v1/customer/orders')->assertUnauthorized();
  CustomerOrder::query()->create(['user_id'=>$this->customer->id,'status'=>'pending','total_amount'=>100]);$other=User::factory()->create();CustomerOrder::query()->create(['user_id'=>$other->id,'status'=>'pending','total_amount'=>200]);
  $this->actingAs($this->customer)->getJson('/api/v1/customer/orders')->assertOk()->assertJsonCount(1,'data');
 }

 public function test_address_default_is_unique_promoted_and_validated():void{
  $first=$this->actingAs($this->customer)->postJson('/api/v1/customer/addresses',['recipient_name'=>'First','phone'=>'010','address_line1'=>'One','city'=>'Cairo','country'=>'EG'])->assertCreated();
  $firstId=$first->json('data.id');
  $second=$this->actingAs($this->customer)->postJson('/api/v1/customer/addresses',['recipient_name'=>'Second','phone'=>'011','address_line1'=>'Two','city'=>'Cairo','country'=>'EG'])->assertCreated();
  $secondId=$second->json('data.id');
  $this->assertDatabaseHas('customer_addresses',['id'=>$firstId,'is_default'=>1]);
  $this->actingAs($this->customer)->patchJson("/api/v1/customer/addresses/{$secondId}",['recipient_name'=>'Second','phone'=>'011','address_line1'=>'Two','city'=>'Cairo','country'=>'EG','is_default'=>true])->assertOk();
  $this->assertDatabaseHas('customer_addresses',['id'=>$firstId,'is_default'=>0]);
  $this->actingAs($this->customer)->deleteJson("/api/v1/customer/addresses/{$secondId}")->assertNoContent();
  $this->assertDatabaseHas('customer_addresses',['id'=>$firstId,'is_default'=>1]);
  $this->actingAs($this->customer)->postJson('/api/v1/customer/addresses',['recipient_name'=>'Invalid','phone'=>'012','address_line1'=>'Three','city'=>'Cairo','country'=>'EGY'])->assertUnprocessable()->assertJsonValidationErrors(['country']);
 }
}
