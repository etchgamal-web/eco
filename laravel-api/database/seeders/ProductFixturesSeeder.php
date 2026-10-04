<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Models\Attribute;
use App\Modules\Catalog\Infrastructure\Models\AttributeValue;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::query()->updateOrCreate(
            ['slug' => 'eco-home'],
            ['name' => 'Eco Home', 'status' => 'active']
        );

        $categories = [];
        foreach ([
            ['slug' => 'home-essentials', 'name' => 'Home Essentials'],
            ['slug' => 'kitchen-dining', 'name' => 'Kitchen & Dining'],
            ['slug' => 'personal-care', 'name' => 'Personal Care'],
            ['slug' => 'outdoor-living', 'name' => 'Outdoor Living'],
        ] as $categoryData) {
            $categories[$categoryData['slug']] = Category::query()->updateOrCreate(
                ['slug' => $categoryData['slug']],
                ['name' => $categoryData['name'], 'is_active' => true]
            );
        }

        $finish = Attribute::query()->updateOrCreate(['name' => 'Finish'], []);
        $clearFinish = AttributeValue::query()->updateOrCreate(
            ['attribute_id' => $finish->id, 'value' => 'Clear'],
            []
        );
        $amberFinish = AttributeValue::query()->updateOrCreate(
            ['attribute_id' => $finish->id, 'value' => 'Amber'],
            []
        );

        $products = [
            [
                'name' => 'Recycled Cotton Tote',
                'slug' => 'recycled-cotton-tote',
                'description' => 'Reusable everyday tote made from recycled cotton.',
                'type' => 'simple',
                'price' => 3200,
                'category' => 'home-essentials',
                'variants' => [],
            ],
            [
                'name' => 'Bamboo Toothbrush Set',
                'slug' => 'bamboo-toothbrush-set',
                'description' => 'A family set of compostable bamboo toothbrushes.',
                'type' => 'simple',
                'price' => 850,
                'category' => 'personal-care',
                'variants' => [],
            ],
            [
                'name' => 'Glass Storage Set',
                'slug' => 'glass-storage-set',
                'description' => 'Reusable kitchen storage jars in two finishes.',
                'type' => 'variable',
                'price' => 5400,
                'category' => 'kitchen-dining',
                'variants' => [
                    [
                        'sku' => 'ECO-STORAGE-CLEAR',
                        'price' => 5400,
                        'compare_at_price' => 6200,
                        'weight' => 1.200,
                        'finish' => 'clear',
                        'attribute_value' => $clearFinish,
                    ],
                    [
                        'sku' => 'ECO-STORAGE-AMBER',
                        'price' => 5800,
                        'compare_at_price' => 6600,
                        'weight' => 1.250,
                        'finish' => 'amber',
                        'attribute_value' => $amberFinish,
                    ],
                ],
            ],
            [
                'name' => 'Solar Garden Lantern',
                'slug' => 'solar-garden-lantern',
                'description' => 'Rechargeable solar lantern for patios and gardens.',
                'type' => 'simple',
                'price' => 7800,
                'category' => 'outdoor-living',
                'variants' => [],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'type' => $productData['type'],
                    'status' => 'active',
                    'price' => $productData['price'],
                    'brand_id' => $brand->id,
                    'category_id' => $categories[$productData['category']]->id,
                ]
            );

            foreach ($productData['variants'] as $variantData) {
                $variant = ProductVariant::query()->updateOrCreate(
                    ['sku' => $variantData['sku']],
                    [
                        'product_id' => $product->id,
                        'price' => $variantData['price'],
                        'compare_at_price' => $variantData['compare_at_price'],
                        'weight' => $variantData['weight'],
                        'status' => 'active',
                        'variant_data' => ['finish' => $variantData['finish']],
                        'combination_hash' => hash('sha256', 'finish:'.$variantData['finish']),
                    ]
                );

                $variant->attributeValues()->sync([
                    $variantData['attribute_value']->id => ['attribute_id' => $finish->id],
                ]);
            }
        }
    }
}
