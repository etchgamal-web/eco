<?php
namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->admin = $this->createAdminUser();
    }

    public function test_settings_update_list_group_show_and_update_existing_value(): void
    {
        $this->actingAs($this->admin)->putJson('/api/v1/settings/store.name', [
            'group' => 'store', 'key' => 'ignored.key', 'value' => 'My Shop',
            'type' => 'string', 'description' => 'Store name',
        ])->assertOk()->assertJsonPath('data.key', 'store.name')->assertJsonPath('data.value', 'My Shop');
        $this->putJson('/api/v1/settings/store.enabled', [
            'group' => 'store', 'value' => true, 'type' => 'boolean',
        ])->assertOk()->assertJsonPath('data.value', true);
        $this->putJson('/api/v1/settings/orders.limit', [
            'group' => 'orders', 'value' => 25, 'type' => 'integer',
        ])->assertOk()->assertJsonPath('data.value', 25);

        $this->getJson('/api/v1/settings')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/settings/groups/store')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/settings/store.enabled')->assertOk()
            ->assertJsonPath('data.type', 'boolean')->assertJsonPath('data.value', true);
        $this->putJson('/api/v1/settings/store.enabled', [
            'group' => 'store', 'value' => false, 'type' => 'boolean', 'description' => null,
        ])->assertOk()->assertJsonPath('data.value', false);
        $this->assertDatabaseCount('settings', 3);
    }

    public function test_settings_support_json_float_and_nullable_string_values(): void
    {
        $this->actingAs($this->admin)->putJson('/api/v1/settings/theme.options', [
            'group' => 'theme', 'value' => ['dark' => true], 'type' => 'json',
        ])->assertOk()->assertJsonPath('data.value.dark', true);
        $this->putJson('/api/v1/settings/tax.rate', [
            'group' => 'tax', 'value' => 7.5, 'type' => 'float',
        ])->assertOk()->assertJsonPath('data.value', 7.5);
        $this->putJson('/api/v1/settings/store.tagline', [
            'group' => 'store', 'value' => null, 'type' => 'string',
        ])->assertOk()->assertJsonPath('data.value', null);
    }

    public function test_missing_setting_returns_404_and_empty_group_returns_an_empty_list(): void
    {
        $this->actingAs($this->admin)->getJson('/api/v1/settings/missing')->assertNotFound()
            ->assertJsonPath('message', 'Setting [missing] was not found.');
        $this->getJson('/api/v1/settings/groups/missing')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_setting_validation_covers_required_fields_and_type_specific_values(): void
    {
        $this->actingAs($this->admin)->putJson('/api/v1/settings')->assertUnprocessable()
            ->assertJsonValidationErrors(['group', 'key', 'value', 'type']);
        $this->putJson('/api/v1/settings/invalid.type', [
            'group' => 'test', 'value' => 'x', 'type' => 'unsupported',
        ])->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->putJson('/api/v1/settings/invalid.boolean', [
            'group' => 'test', 'value' => 'not-a-bool', 'type' => 'boolean',
        ])->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->putJson('/api/v1/settings/invalid.integer', [
            'group' => 'test', 'value' => 'one', 'type' => 'integer',
        ])->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->putJson('/api/v1/settings/invalid.json', [
            'group' => 'test', 'value' => 'not-an-array', 'type' => 'json',
        ])->assertUnprocessable()->assertJsonValidationErrors('value');
    }

    public function test_view_and_update_permissions_are_independent(): void
    {
        Setting::query()->create(['group' => 'store', 'key' => 'store.name', 'value' => 'Shop', 'type' => 'string']);
        $viewer = $this->createUserWithPermissions(['settings.view']);
        $updater = $this->createUserWithPermissions(['settings.update']);

        $this->actingAs($viewer)->getJson('/api/v1/settings')->assertOk();
        $this->putJson('/api/v1/settings/store.name', [
            'group' => 'store', 'value' => 'No', 'type' => 'string',
        ])->assertForbidden();
        $this->actingAs($updater)->getJson('/api/v1/settings')->assertForbidden();
        $this->putJson('/api/v1/settings/store.name', [
            'group' => 'store', 'value' => 'Updated', 'type' => 'string',
        ])->assertOk();
    }

    public function test_abandoned_cart_scan_time_accepts_valid_time_and_rejects_invalid_time(): void
    {
        $this->actingAs($this->admin)->putJson('/api/v1/settings/cart.abandoned_scan_time', [
            'group' => 'cart', 'value' => '02:15', 'type' => 'string',
        ])->assertOk()->assertJsonPath('data.value', '02:15');

        $this->actingAs($this->admin)->putJson('/api/v1/settings/cart.abandoned_scan_time', [
            'group' => 'cart', 'value' => '25:99', 'type' => 'string',
        ])->assertUnprocessable()->assertJsonValidationErrors('value');
    }
}
