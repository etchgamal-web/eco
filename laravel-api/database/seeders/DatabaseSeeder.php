<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            PaymentGatewaySettingsSeeder::class,
            CheckoutSettingsSeeder::class,
            BackupSettingsSeeder::class,
            CatalogSettingsSeeder::class,
            LocalizationSettingsSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoDataSeeder::class,
                ProductFixturesSeeder::class,
                InventoryFixturesSeeder::class,
                CatalogFixturesSeeder::class,
                PromotionTaxFixturesSeeder::class,
                OrderFixturesSeeder::class,
                PaymentFixturesSeeder::class,
                ShippingFixturesSeeder::class,
                ReturnFixturesSeeder::class,
                SocialCommerceFixturesSeeder::class,
                MarketingFixturesSeeder::class,
                OperationsFixturesSeeder::class,
            ]);
        }
    }
}
