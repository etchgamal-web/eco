<?php
namespace Tests\Feature;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Role;
use App\Models\User;
use App\Modules\Promotion\Domain\Contracts\CouponServiceInterface;
use App\Modules\Promotion\Domain\Exceptions\CouponInvalidException;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
final class PromotionTaxManagementApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_manage_coupons_and_tax_rules(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $coupon = $this->actingAs($admin)->postJson('/api/v1/coupons', ['code' => 'save10', 'type' => 'percent', 'value' => 10, 'is_active' => true])->assertCreated()->json('data.id');
        $this->actingAs($admin)->getJson('/api/v1/coupons')->assertOk()->assertJsonPath('data.0.code', 'SAVE10');
        $this->actingAs($admin)->patchJson("/api/v1/coupons/{$coupon}", ['value' => 15])->assertOk()->assertJsonPath('data.value', 15);
        $tax = $this->actingAs($admin)->postJson('/api/v1/tax-rules', ['name' => 'Egypt VAT', 'country' => 'eg', 'rate' => 14])->assertCreated()->json('data.id');
        $this->actingAs($admin)->getJson('/api/v1/tax-rules')->assertOk()->assertJsonPath('data.0.name', 'Egypt VAT');
        $this->actingAs($admin)->deleteJson("/api/v1/tax-rules/{$tax}")->assertNoContent();
        $this->actingAs($admin)->deleteJson("/api/v1/coupons/{$coupon}")->assertNoContent();
    }
    public function test_customer_cannot_manage_promotions_or_taxes(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = User::factory()->create();
        $customer->roles()->attach(Role::query()->where('slug', 'customer')->firstOrFail());
        $this->actingAs($customer)->getJson('/api/v1/coupons')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/v1/tax-rules')->assertForbidden();
    }

    public function test_coupon_management_validates_business_values_and_usage_limits_are_enforced(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson('/api/v1/coupons', [])->assertUnprocessable();
        $this->actingAs($admin)->postJson('/api/v1/coupons', [
            'code' => 'too-high', 'type' => 'percent', 'value' => 101,
        ])->assertUnprocessable()->assertJsonValidationErrors('value');

        $customer = User::factory()->create();
        $coupon = Coupon::query()->create([
            'code' => 'LIMITED', 'type' => 'fixed', 'value' => 100,
            'per_user_limit' => 1, 'usage_limit' => 2, 'is_active' => true,
        ]);
        $service = $this->app->make(CouponServiceInterface::class);

        $this->assertSame(['code' => 'LIMITED', 'discount' => 100], $service->apply('limited', $customer->id, 500));
        CouponUsage::query()->create(['coupon_id' => $coupon->id, 'user_id' => $customer->id, 'discount_amount' => 100]);

        $this->expectException(CouponInvalidException::class);
        $service->apply('LIMITED', $customer->id, 500);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());
        return $user;
    }
}
