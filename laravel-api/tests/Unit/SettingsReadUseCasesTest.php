<?php
namespace Tests\Unit;

use App\Models\Setting;
use App\Modules\Settings\Application\UseCases\GetSetting;
use App\Modules\Settings\Application\UseCases\GetSettingRecord;
use App\Modules\Settings\Application\UseCases\GetSettingsByGroup;
use App\Modules\Settings\Application\UseCases\ListSettings;
use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Domain\Exceptions\SettingsNotFoundException;
use Mockery;
use PHPUnit\Framework\TestCase;

class SettingsReadUseCasesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_and_group_use_cases_delegate_to_contract(): void
    {
        $settings = collect([new Setting(['key' => 'store.name'])]);
        $repository = Mockery::mock(SettingsRepositoryInterface::class);
        $repository->shouldReceive('getAll')->once()->andReturn($settings);
        $repository->shouldReceive('getByGroup')->once()->with('store')->andReturn($settings);

        self::assertSame($settings, (new ListSettings($repository))->execute());
        self::assertSame($settings, (new GetSettingsByGroup($repository))->execute('store'));
    }

    public function test_get_setting_returns_the_typed_value_and_default(): void
    {
        $setting = new Setting(['key' => 'store.name']);
        $repository = Mockery::mock(SettingsRepositoryInterface::class);
        $repository->shouldReceive('findByKey')->once()->with('store.name')->andReturn($setting);

        self::assertSame(null, (new GetSetting($repository))->execute('store.name'));

        $missing = Mockery::mock(SettingsRepositoryInterface::class);
        $missing->shouldReceive('findByKey')->once()->with('missing')->andReturnNull();
        self::assertSame('fallback', (new GetSetting($missing))->execute('missing', 'fallback'));
    }

    public function test_get_setting_throws_domain_not_found_exception(): void
    {
        $repository = Mockery::mock(SettingsRepositoryInterface::class);
        $repository->shouldReceive('findByKey')->once()->with('missing')->andReturnNull();

        $this->expectException(SettingsNotFoundException::class);
        (new GetSettingRecord($repository))->execute('missing');
    }
}
