<?php
namespace App\Modules\Order\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Order\Application\UseCases\ManageReturns;
use App\Modules\Order\Presentation\Http\Requests\ReturnRequest;
use Illuminate\Http\JsonResponse;
final class ReturnController extends Controller
{
    public function customerIndex(ReturnRequest $request, ManageReturns $useCase): JsonResponse { return response()->json(['data' => $useCase->customerList((int) $request->user()->id)]); }
    public function store(ReturnRequest $request, int $order, ManageReturns $useCase): JsonResponse { return response()->json(['data' => $useCase->submit((int) $request->user()->id, $order, $request->validated())], 201); }
    public function index(ReturnRequest $request, ManageReturns $useCase): JsonResponse { return response()->json(['data' => $useCase->adminList()]); }
    public function approve(ReturnRequest $request, int $return, ManageReturns $useCase): JsonResponse { return response()->json(['data' => $useCase->approve($return)]); }
    public function reject(ReturnRequest $request, int $return, ManageReturns $useCase): JsonResponse { return response()->json(['data' => $useCase->reject($return, $request->validated('reason'))]); }
}
