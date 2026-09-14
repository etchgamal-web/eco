<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],

            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'products'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'group' => 'products'],
            ['name' => 'Update Products', 'slug' => 'products.update', 'group' => 'products'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'group' => 'products'],
            ['name' => 'View Product Variants', 'slug' => 'products.variants.view', 'group' => 'products'],
            ['name' => 'Create Product Variants', 'slug' => 'products.variants.create', 'group' => 'products'],
            ['name' => 'Update Product Variants', 'slug' => 'products.variants.update', 'group' => 'products'],
            ['name' => 'Delete Product Variants', 'slug' => 'products.variants.delete', 'group' => 'products'],

            // Attributes
            ['name' => 'View Attributes', 'slug' => 'attributes.view', 'group' => 'catalog'],
            ['name' => 'Create Attributes', 'slug' => 'attributes.create', 'group' => 'catalog'],
            ['name' => 'Update Attributes', 'slug' => 'attributes.update', 'group' => 'catalog'],
            ['name' => 'Delete Attributes', 'slug' => 'attributes.delete', 'group' => 'catalog'],

            // Categories
            ['name' => 'View Categories', 'slug' => 'categories.view', 'group' => 'catalog'],
            ['name' => 'Create Categories', 'slug' => 'categories.create', 'group' => 'catalog'],
            ['name' => 'Update Categories', 'slug' => 'categories.update', 'group' => 'catalog'],
            ['name' => 'Delete Categories', 'slug' => 'categories.delete', 'group' => 'catalog'],

            // Brands
            ['name' => 'View Brands', 'slug' => 'brands.view', 'group' => 'catalog'],
            ['name' => 'Create Brands', 'slug' => 'brands.create', 'group' => 'catalog'],
            ['name' => 'Update Brands', 'slug' => 'brands.update', 'group' => 'catalog'],
            ['name' => 'Delete Brands', 'slug' => 'brands.delete', 'group' => 'catalog'],

            // Promotions and Taxes
            ['name' => 'View Promotions', 'slug' => 'promotions.view', 'group' => 'promotions'],
            ['name' => 'Create Promotions', 'slug' => 'promotions.create', 'group' => 'promotions'],
            ['name' => 'Update Promotions', 'slug' => 'promotions.update', 'group' => 'promotions'],
            ['name' => 'Delete Promotions', 'slug' => 'promotions.delete', 'group' => 'promotions'],
            ['name' => 'View Taxes', 'slug' => 'taxes.view', 'group' => 'taxes'],
            ['name' => 'Create Taxes', 'slug' => 'taxes.create', 'group' => 'taxes'],
            ['name' => 'Update Taxes', 'slug' => 'taxes.update', 'group' => 'taxes'],
            ['name' => 'Delete Taxes', 'slug' => 'taxes.delete', 'group' => 'taxes'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => 'Adjust Inventory', 'slug' => 'inventory.adjust', 'group' => 'inventory'],
            ['name' => 'Transfer Inventory', 'slug' => 'inventory.transfer', 'group' => 'inventory'],

            // Orders
            ['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'orders'],
            ['name' => 'Verify Orders', 'slug' => 'orders.verify', 'group' => 'orders'],
            ['name' => 'Confirm Orders', 'slug' => 'orders.confirm', 'group' => 'orders'],
            ['name' => 'Edit Orders', 'slug' => 'orders.edit', 'group' => 'orders'],
            ['name' => 'Manage Orders', 'slug' => 'orders.manage', 'group' => 'orders'],
            ['name' => 'Cancel Orders', 'slug' => 'orders.cancel', 'group' => 'orders'],
            ['name' => 'Cancel Orders After Shipping', 'slug' => 'orders.cancel_after_shipping', 'group' => 'orders'],
            ['name' => 'Refund Orders', 'slug' => 'orders.refund', 'group' => 'orders'],
            ['name' => 'Return Orders', 'slug' => 'orders.return', 'group' => 'orders'],

            // Customers
            ['name' => 'View Customers', 'slug' => 'customers.view', 'group' => 'customers'],
            ['name' => 'Create Customers', 'slug' => 'customers.create', 'group' => 'customers'],
            ['name' => 'Update Customers', 'slug' => 'customers.update', 'group' => 'customers'],
            ['name' => 'View Own Customer Profile', 'slug' => 'customer.profile.view', 'group' => 'customer-profile'],
            ['name' => 'Update Own Customer Profile', 'slug' => 'customer.profile.update', 'group' => 'customer-profile'],
            ['name' => 'View Customer Addresses', 'slug' => 'customer.addresses.view', 'group' => 'customer-profile'],
            ['name' => 'Manage Customer Addresses', 'slug' => 'customer.addresses.manage', 'group' => 'customer-profile'],
            ['name' => 'View Customer Orders', 'slug' => 'customer.orders.view', 'group' => 'customer-profile'],
            ['name' => 'Manage Own Customer Orders', 'slug' => 'customer.orders.manage', 'group' => 'customer-profile'],
            ['name' => 'View Customer Cart', 'slug' => 'customer.cart.view', 'group' => 'customer-profile'],
            ['name' => 'Manage Customer Cart', 'slug' => 'customer.cart.manage', 'group' => 'customer-profile'],
            ['name' => 'View Customer Wishlist', 'slug' => 'customer.wishlist.view', 'group' => 'customer-profile'],
            ['name' => 'Manage Customer Wishlist', 'slug' => 'customer.wishlist.manage', 'group' => 'customer-profile'],
            ['name' => 'Manage Customer Preferences', 'slug' => 'customer.preferences.manage', 'group' => 'customer-profile'],
            ['name' => 'View Customer Notifications', 'slug' => 'customer.notifications.view', 'group' => 'customer-profile'],

            // Assistants
            ['name' => 'View Assistants', 'slug' => 'assistants.view', 'group' => 'assistants'],
            ['name' => 'Create Assistants', 'slug' => 'assistants.create', 'group' => 'assistants'],
            ['name' => 'Update Assistants', 'slug' => 'assistants.update', 'group' => 'assistants'],
            ['name' => 'Delete Assistants', 'slug' => 'assistants.delete', 'group' => 'assistants'],

            // Roles & Permissions
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'authorization'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'authorization'],
            ['name' => 'Update Roles', 'slug' => 'roles.update', 'group' => 'authorization'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'authorization'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'group' => 'authorization'],

            // Payments
            ['name' => 'View Payments', 'slug' => 'payments.view', 'group' => 'payments'],
            ['name' => 'Manage Payments', 'slug' => 'payments.manage', 'group' => 'payments'],
            ['name' => 'Refund Payments', 'slug' => 'payments.refund', 'group' => 'payments'],

            // Shipping
            ['name' => 'View Shipping', 'slug' => 'shipping.view', 'group' => 'shipping'],
            ['name' => 'Manage Shipping', 'slug' => 'shipping.manage', 'group' => 'shipping'],

            // Discounts
            ['name' => 'View Discounts', 'slug' => 'discounts.view', 'group' => 'discounts'],
            ['name' => 'Create Discounts', 'slug' => 'discounts.create', 'group' => 'discounts'],
            ['name' => 'Update Discounts', 'slug' => 'discounts.update', 'group' => 'discounts'],
            ['name' => 'Delete Discounts', 'slug' => 'discounts.delete', 'group' => 'discounts'],

            // Affiliates
            ['name' => 'View Affiliates', 'slug' => 'affiliates.view', 'group' => 'affiliates'],
            ['name' => 'Manage Affiliates', 'slug' => 'affiliates.manage', 'group' => 'affiliates'],
            ['name' => 'Manage Affiliate Payouts', 'slug' => 'affiliates.payouts', 'group' => 'affiliates'],

            // Reviews
            ['name' => 'View Reviews', 'slug' => 'reviews.view', 'group' => 'reviews'],
            ['name' => 'Manage Reviews', 'slug' => 'reviews.manage', 'group' => 'reviews'],

            // Wishlist
            ['name' => 'View Wishlist', 'slug' => 'wishlist.view', 'group' => 'wishlist'],

            // CMS
            ['name' => 'View CMS', 'slug' => 'cms.view', 'group' => 'cms'],
            ['name' => 'Manage CMS', 'slug' => 'cms.manage', 'group' => 'cms'],

            // SEO
            ['name' => 'View SEO', 'slug' => 'seo.view', 'group' => 'seo'],
            ['name' => 'Manage SEO', 'slug' => 'seo.manage', 'group' => 'seo'],

            // Settings
            ['name' => 'View Settings', 'slug' => 'settings.view', 'group' => 'settings'],
            ['name' => 'Update Settings', 'slug' => 'settings.update', 'group' => 'settings'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'group' => 'inventory'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $allPermissions = Permission::query()->get();
        $customerProfilePermissions = $allPermissions->whereIn('slug', [
            'customer.profile.view', 'customer.profile.update', 'customer.addresses.view', 'customer.addresses.manage',
            'customer.orders.view', 'customer.orders.manage', 'customer.cart.view', 'customer.cart.manage', 'customer.wishlist.view', 'customer.wishlist.manage',
            'customer.preferences.manage', 'customer.notifications.view',
        ]);
        $productManagerPermissions = $allPermissions->whereIn('slug', [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.variants.view', 'products.variants.create', 'products.variants.update', 'products.variants.delete',
            'attributes.view', 'attributes.create', 'attributes.update', 'attributes.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'inventory.view', 'inventory.manage',
        ]);
        $orderManagerPermissions = $allPermissions->whereIn('slug', [
            'orders.view', 'orders.manage', 'orders.verify', 'orders.confirm', 'orders.edit', 'orders.cancel',
            'inventory.view',
        ]);
        $managerPermissions = $allPermissions->whereIn('slug', [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.variants.view', 'products.variants.create', 'products.variants.update', 'products.variants.delete',
            'attributes.view', 'attributes.create', 'attributes.update', 'attributes.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'orders.view', 'orders.manage', 'orders.verify', 'orders.confirm', 'orders.edit', 'orders.cancel',
            'inventory.view', 'inventory.manage',
        ]);
        $supportAgentPermissions = $allPermissions->whereIn('slug', [
            'customers.view', 'customer.orders.view', 'customer.addresses.view', 'customer.notifications.view', 'orders.view',
        ]);

        $roles = [
            'admin' => [
                'name' => 'Administrator',
                'description' => 'System administrator with full access.',
                'is_system' => true,
                'permissions' => $allPermissions,
            ],

            'owner' => [
                'name' => 'Owner',
                'description' => 'Store owner with full store management access.',
                'is_system' => true,
                'permissions' => $allPermissions,
            ],

            'assistant' => [
                'name' => 'Assistant',
                'description' => 'Store assistant with permissions assigned by the owner.',
                'is_system' => true,
                'permissions' => [],
            ],

            'customer' => [
                'name' => 'Customer',
                'description' => 'Store customer account.',
                'is_system' => true,
                'permissions' => $customerProfilePermissions,
            ],
            'product_manager' => [
                'name' => 'Product Manager',
                'description' => 'Can create products without order management access.',
                'is_system' => true,
                'permissions' => $productManagerPermissions,
            ],
            'order_manager' => [
                'name' => 'Order Manager',
                'description' => 'Can manage orders without product deletion access.',
                'is_system' => true,
                'permissions' => $orderManagerPermissions,
            ],
            'manager' => [
                'name' => 'Manager',
                'description' => 'Manages catalog and operational orders without sensitive administration.',
                'is_system' => true,
                'permissions' => $managerPermissions,
            ],
            'support_agent' => [
                'name' => 'Support Agent',
                'description' => 'Can view customer and order information without mutation access.',
                'is_system' => true,
                'permissions' => $supportAgentPermissions,
            ],
        ];

        foreach ($roles as $slug => $roleData) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_system' => $roleData['is_system'],
                    'is_active' => true,
                ]
            );

            $role->permissions()->sync(
                collect($roleData['permissions'])
                    ->pluck('id')
                    ->all()
            );
        }
    }
}
