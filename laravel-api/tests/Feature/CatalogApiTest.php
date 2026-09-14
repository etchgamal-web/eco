<?php
namespace Tests\Feature;
use App\Models\Permission;use App\Models\Role;use App\Models\User;use Database\Seeders\RbacSeeder;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;
class CatalogApiTest extends TestCase
{
 use RefreshDatabase;
 protected User $user;
 protected function setUp():void{parent::setUp();$this->seed(RbacSeeder::class);$role=Role::query()->where('slug','admin')->firstOrFail();$this->user=User::factory()->create();$this->user->roles()->attach($role);}
 public function test_product_crud_and_missing_product_response():void
 {
  $create=$this->actingAs($this->user)->postJson('/api/v1/products',['name'=>'Phone','type'=>'simple','status'=>'active']);$create->assertCreated()->assertJsonPath('data.slug','phone');$id=$create->json('data.id');
  $this->actingAs($this->user)->getJson("/api/v1/products/{$id}")->assertOk();
  $this->actingAs($this->user)->patchJson("/api/v1/products/{$id}",['name'=>'Smart Phone','type'=>'simple','status'=>'active'])->assertOk();
  $this->actingAs($this->user)->deleteJson("/api/v1/products/{$id}")->assertNoContent();
  $this->actingAs($this->user)->getJson("/api/v1/products/{$id}")->assertNotFound();
 }
 public function test_simple_product_rejects_variants_and_variable_product_enforces_uniqueness():void
 {
  $a=$this->actingAs($this->user)->postJson('/api/v1/attributes',['name'=>'Color'])->json('data.id');$v=$this->actingAs($this->user)->postJson("/api/v1/attributes/{$a}/values",['value'=>'Red'])->json('data.id');
  $simple=$this->actingAs($this->user)->postJson('/api/v1/products',['name'=>'Simple','type'=>'simple','status'=>'active'])->json('data.id');
  $payload=['sku'=>'SKU-1','price'=>10,'status'=>'active','attribute_value_ids'=>[$v]];
  $this->actingAs($this->user)->postJson("/api/v1/products/{$simple}/variants",$payload)->assertConflict();
  $variable=$this->actingAs($this->user)->postJson('/api/v1/products',['name'=>'Variable','type'=>'variable','status'=>'active'])->json('data.id');
  $this->actingAs($this->user)->postJson("/api/v1/products/{$variable}/variants",$payload)->assertCreated();
  $this->actingAs($this->user)->postJson("/api/v1/products/{$variable}/variants",array_merge($payload,['sku'=>'SKU-2']))->assertConflict();
 }
  public function test_storefront_catalog_is_public_but_mutations_require_permissions():void
 {
  $this->postJson('/api/v1/products',[])->assertUnauthorized();
  $this->getJson('/api/v1/products')->assertOk();
  $user=User::factory()->create();$this->actingAs($user)->postJson('/api/v1/products',[])->assertForbidden();
 }
}
