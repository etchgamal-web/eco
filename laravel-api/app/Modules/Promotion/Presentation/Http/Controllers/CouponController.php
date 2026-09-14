<?php
namespace App\Modules\Promotion\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Promotion\Application\UseCases\ManageCoupons;
use App\Modules\Promotion\Presentation\Http\Requests\PromotionManagementRequest;
use Illuminate\Http\JsonResponse;
final class CouponController extends Controller
{
    public function index(PromotionManagementRequest $request, ManageCoupons $useCase): JsonResponse { return response()->json(['data' => $useCase->list()]); }
    public function store(PromotionManagementRequest $request, ManageCoupons $useCase): JsonResponse { return response()->json(['data' => $useCase->store($request->validated())], 201); }
    public function show(PromotionManagementRequest $request, int $id, ManageCoupons $useCase): JsonResponse { return response()->json(['data' => $useCase->show($id)]); }
    public function update(PromotionManagementRequest $request, int $id, ManageCoupons $useCase): JsonResponse { return response()->json(['data' => $useCase->update($id, $request->validated())]); }
    public function destroy(PromotionManagementRequest $request, int $id, ManageCoupons $useCase): JsonResponse { $useCase->remove($id); return response()->json(null, 204); }
}
