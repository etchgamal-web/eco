<?php
namespace App\Modules\Settings\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Domain\ValueObjects\SettingData;
use App\Modules\Settings\Domain\ValueObjects\SettingsData;
use App\Modules\Settings\Application\UseCases\GetSettingRecord;
use App\Modules\Settings\Application\UseCases\GetSettingsByGroup;
use App\Modules\Settings\Application\UseCases\ListSettings;
use App\Modules\Settings\Application\UseCases\UpdateSetting;
use App\Modules\Settings\Presentation\Http\Requests\UpdateSettingsRequest;
use App\Modules\Settings\Presentation\Http\Requests\ViewSettingsRequest;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function index(ViewSettingsRequest $request, ListSettings $useCase): JsonResponse
    {
        return response()->json(['data' => SettingsData::fromModels($useCase->execute())->toArray()]);
    }

    public function group(ViewSettingsRequest $request, string $group, GetSettingsByGroup $useCase): JsonResponse
    {
        return response()->json(['data' => SettingsData::fromModels($useCase->execute($group))->toArray()]);
    }

    public function show(ViewSettingsRequest $request, string $key, GetSettingRecord $useCase): JsonResponse
    {
        return response()->json(['data' => SettingData::fromModel($useCase->execute($key))->toArray()]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSetting $useCase): JsonResponse
    {
        return response()->json(['data' => SettingData::fromModel($useCase->execute(SettingData::fromArray($request->validated())))->toArray()]);
    }
}
