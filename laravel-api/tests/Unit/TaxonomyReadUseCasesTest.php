<?php
namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Modules\Catalog\Application\UseCases\Brands\GetBrand;
use App\Modules\Catalog\Application\UseCases\Brands\ListBrands;
use App\Modules\Catalog\Application\UseCases\Categories\GetCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ListCategories;
use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class TaxonomyReadUseCasesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_brand_read_use_cases_delegate_to_contract(): void
    {
        $brand = new Brand(['name' => 'Acme']);
        $items = collect([$brand]);
        $repository = Mockery::mock(BrandRepositoryInterface::class);
        $repository->shouldReceive('all')->once()->andReturn($items);
        $repository->shouldReceive('findOrFail')->once()->with(1)->andReturn($brand);

        self::assertSame($items, (new ListBrands($repository))->execute());
        self::assertSame($brand, (new GetBrand($repository))->execute(1));
    }

    public function test_category_read_use_cases_delegate_to_contract(): void
    {
        $category = new Category(['name' => 'Phones']);
        $items = collect([$category]);
        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('all')->once()->andReturn($items);
        $repository->shouldReceive('findOrFail')->once()->with(1)->andReturn($category);

        self::assertSame($items, (new ListCategories($repository))->execute());
        self::assertSame($category, (new GetCategory($repository))->execute(1));
    }
}
