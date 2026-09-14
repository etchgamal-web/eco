<?php
namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Modules\Catalog\Application\UseCases\Products\GetProduct;
use App\Modules\Catalog\Application\UseCases\Products\GetProductVariant;
use App\Modules\Catalog\Application\UseCases\Products\ListProducts;
use App\Modules\Catalog\Application\UseCases\Products\ListProductVariants;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class ProductReadUseCasesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_products_delegates_to_repository(): void
    {
        $products = collect([new Product(['name' => 'One'])]);
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $repository->shouldReceive('all')->once()->andReturn($products);

        self::assertSame($products, (new ListProducts($repository))->execute());
    }

    public function test_get_product_delegates_to_repository(): void
    {
        $product = new Product(['name' => 'One']);
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $repository->shouldReceive('findOrFail')->once()->with(10)->andReturn($product);

        self::assertSame($product, (new GetProduct($repository))->execute(10));
    }

    public function test_list_variants_resolves_parent_before_listing(): void
    {
        $product = new Product(['name' => 'Variable']);
        $variants = new Collection([new ProductVariant(['sku' => 'SKU'])]);
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $repository->shouldReceive('findOrFail')->once()->with(10)->andReturn($product);
        $repository->shouldReceive('variants')->once()->with(10)->andReturn($variants);

        self::assertSame($variants, (new ListProductVariants($repository))->execute(10));
    }

    public function test_get_variant_is_scoped_to_its_parent_product(): void
    {
        $product = new Product(['name' => 'Variable']);
        $variant = new ProductVariant(['sku' => 'SKU']);
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $repository->shouldReceive('findOrFail')->once()->with(10)->andReturn($product);
        $repository->shouldReceive('findVariantOrFail')->once()->with(10, 20)->andReturn($variant);

        self::assertSame($variant, (new GetProductVariant($repository))->execute(10, 20));
    }
}
