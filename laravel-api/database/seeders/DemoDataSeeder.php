<?php

namespace Database\Seeders;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Attribute;
use App\Modules\Catalog\Infrastructure\Models\AttributeValue;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Customer\Infrastructure\Models\CustomerAddress;
use App\Modules\Customer\Infrastructure\Models\CustomerCart;
use App\Modules\Customer\Infrastructure\Models\CustomerCartItem;
use App\Modules\Customer\Infrastructure\Models\CustomerPreference;
use App\Modules\Customer\Infrastructure\Models\CustomerWishlist;
use App\Modules\Inventory\Infrastructure\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed deterministic demo data for local development and API testing.
     *
     * This seeder is idempotent: running it more than once updates the same
     * records instead of creating duplicates.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Administrator',
                'phone' => '+201000000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        $customer = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Demo Customer',
                'phone' => '+201000000002',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        $admin->roles()->syncWithoutDetaching([
            Role::query()->where('slug', 'admin')->value('id'),
        ]);
        $customer->roles()->syncWithoutDetaching([
            Role::query()->where('slug', 'customer')->value('id'),
        ]);

        $brand = Brand::updateOrCreate(
            ['slug' => 'eco-home'],
            ['name' => 'Eco Home', 'status' => 'active']
        );

        $category = Category::updateOrCreate(
            ['slug' => 'home-essentials'],
            ['name' => 'Home Essentials', 'is_active' => true]
        );

        $color = Attribute::updateOrCreate(['name' => 'Color'], []);
        $material = Attribute::updateOrCreate(['name' => 'Material'], []);
        $white = AttributeValue::updateOrCreate(
            ['attribute_id' => $color->id, 'value' => 'White'],
            []
        );
        $bamboo = AttributeValue::updateOrCreate(
            ['attribute_id' => $material->id, 'value' => 'Bamboo'],
            []
        );

        $product = Product::updateOrCreate(
            ['slug' => 'reusable-bamboo-bottle'],
            [
                'name' => 'Reusable Bamboo Bottle',
                'description' => 'A durable reusable bottle for everyday use.',
                'type' => 'variable',
                'status' => 'active',
                'price' => 4500,
                'brand_id' => $brand->id,
                'category_id' => $category->id,
            ]
        );

        $variant = ProductVariant::updateOrCreate(
            ['sku' => 'ECO-BOTTLE-WHITE-BAMBOO'],
            [
                'product_id' => $product->id,
                'price' => 4500,
                'compare_at_price' => 5000,
                'weight' => 0.350,
                'status' => 'active',
                'variant_data' => ['color' => 'White', 'material' => 'Bamboo'],
                'combination_hash' => hash('sha256', 'color:white|material:bamboo'),
            ]
        );
        $variant->attributeValues()->sync([
            $white->id => ['attribute_id' => $color->id],
            $bamboo->id => ['attribute_id' => $material->id],
        ]);

        InventoryItem::updateOrCreate(
            ['product_id' => $product->id, 'variant_id' => $variant->id],
            ['on_hand' => 48, 'reserved' => 3]
        );

        CustomerAddress::updateOrCreate(
            ['user_id' => $customer->id, 'label' => 'home'],
            [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'address_line1' => '12 Nile Street',
                'address_line2' => 'Apartment 4',
                'city' => 'Cairo',
                'state' => 'Cairo',
                'postal_code' => '11511',
                'country' => 'EG',
                'is_default' => true,
            ]
        );

        $cart = CustomerCart::updateOrCreate(
            ['user_id' => $customer->id],
            ['last_activity_at' => now()]
        );
        CustomerCartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id, 'variant_id' => $variant->id],
            ['quantity' => 2]
        );

        CustomerPreference::updateOrCreate(
            ['user_id' => $customer->id],
            ['data' => ['currency' => 'EGP', 'language' => 'en', 'marketing_emails' => true]]
        );
        CustomerWishlist::updateOrCreate(
            ['user_id' => $customer->id, 'product_id' => $product->id],
            []
        );
    }
}
