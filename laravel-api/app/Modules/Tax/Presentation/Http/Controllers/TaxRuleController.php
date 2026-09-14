<?php
namespace App\Modules\Tax\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Tax\Application\UseCases\ManageTaxRules;
use App\Modules\Tax\Presentation\Http\Requests\TaxRuleManagementRequest;
use Illuminate\Http\JsonResponse;
final class TaxRuleController extends Controller
{
    public function index(TaxRuleManagementRequest $request, ManageTaxRules $useCase): JsonResponse { return response()->json(['data' => $useCase->list()]); }
    public function store(TaxRuleManagementRequest $request, ManageTaxRules $useCase): JsonResponse { return response()->json(['data' => $useCase->store($request->validated())], 201); }
    public function show(TaxRuleManagementRequest $request, int $id, ManageTaxRules $useCase): JsonResponse { return response()->json(['data' => $useCase->show($id)]); }
    public function update(TaxRuleManagementRequest $request, int $id, ManageTaxRules $useCase): JsonResponse { return response()->json(['data' => $useCase->update($id, $request->validated())]); }
    public function destroy(TaxRuleManagementRequest $request, int $id, ManageTaxRules $useCase): JsonResponse { $useCase->remove($id); return response()->json(null, 204); }
}
