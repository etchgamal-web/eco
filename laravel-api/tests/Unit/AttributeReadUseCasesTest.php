<?php
namespace Tests\Unit;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Modules\Catalog\Application\UseCases\Attributes\GetAttribute;
use App\Modules\Catalog\Application\UseCases\Attributes\GetAttributeValue;
use App\Modules\Catalog\Application\UseCases\Attributes\ListAttributes;
use App\Modules\Catalog\Application\UseCases\Attributes\ListAttributeValues;
use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class AttributeReadUseCasesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_attribute_list_and_get_delegate_to_contract(): void
    {
        $attribute = new Attribute(['name' => 'Color']);
        $items = collect([$attribute]);
        $repository = Mockery::mock(AttributeRepositoryInterface::class);
        $repository->shouldReceive('all')->once()->andReturn($items);
        $repository->shouldReceive('findOrFail')->once()->with(1)->andReturn($attribute);

        self::assertSame($items, (new ListAttributes($repository))->execute());
        self::assertSame($attribute, (new GetAttribute($repository))->execute(1));
    }

    public function test_value_list_validates_parent_before_querying_values(): void
    {
        $attribute = new Attribute(['name' => 'Color']);
        $values = collect([new AttributeValue(['value' => 'Red'])]);
        $attributes = Mockery::mock(AttributeRepositoryInterface::class);
        $valueRepository = Mockery::mock(AttributeValueRepositoryInterface::class);
        $attributes->shouldReceive('findOrFail')->once()->with(1)->andReturn($attribute);
        $valueRepository->shouldReceive('forAttribute')->once()->with(1)->andReturn($values);

        self::assertSame($values, (new ListAttributeValues($attributes, $valueRepository))->execute(1));
    }

    public function test_get_value_is_scoped_to_parent_attribute(): void
    {
        $attribute = new Attribute(['name' => 'Color']);
        $value = new AttributeValue(['value' => 'Red']);
        $attributes = Mockery::mock(AttributeRepositoryInterface::class);
        $values = Mockery::mock(AttributeValueRepositoryInterface::class);
        $attributes->shouldReceive('findOrFail')->once()->with(1)->andReturn($attribute);
        $values->shouldReceive('findOrFail')->once()->with(2, 1)->andReturn($value);

        self::assertSame($value, (new GetAttributeValue($attributes, $values))->execute(1, 2));
    }
}
