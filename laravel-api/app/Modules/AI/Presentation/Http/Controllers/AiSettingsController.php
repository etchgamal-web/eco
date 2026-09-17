<?php

namespace App\Modules\AI\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AI\Application\UseCases\ManageAiSettings;
use App\Modules\AI\Presentation\Http\Requests\AiSettingsRequest;
use Illuminate\Http\JsonResponse;

final class AiSettingsController extends Controller
{
    public function show(AiSettingsRequest $r, ManageAiSettings $u): JsonResponse
    {
        return response()->json(['data' => $u->view()]);
    }

    public function update(AiSettingsRequest $r, ManageAiSettings $u): JsonResponse
    {
        return response()->json(['data' => $u->update($r->validated())]);
    }
}
