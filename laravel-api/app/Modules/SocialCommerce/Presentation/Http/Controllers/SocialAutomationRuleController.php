<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialCommerce\Application\UseCases\ManageAutomationRule;
use App\Modules\SocialCommerce\Presentation\Http\Requests\AutomationRuleRequest;
use Illuminate\Http\JsonResponse;

final class SocialAutomationRuleController extends Controller
{
    public function index(AutomationRuleRequest $request, ManageAutomationRule $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->list($request->validated())]);
    }

    public function store(AutomationRuleRequest $request, ManageAutomationRule $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->saveRule($request->validated())], 201);
    }

    public function update(AutomationRuleRequest $request, int $rule, ManageAutomationRule $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->update($rule, $request->validated())]);
    }

    public function destroy(AutomationRuleRequest $request, int $rule, ManageAutomationRule $useCase): JsonResponse
    {
        $useCase->removeRule($rule);

        return response()->noContent();
    }
}
