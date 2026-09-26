<?php

namespace App\Modules\Customer\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Application\UseCases\GetAdminCustomers;
use App\Modules\Customer\Presentation\Http\Requests\CustomerAdminRequest;
use Illuminate\Http\JsonResponse;

final class CustomerAdminController extends Controller
{
    public function index(CustomerAdminRequest $request, GetAdminCustomers $customers): JsonResponse
    {
        $result = $customers->index(
            trim((string) $request->validated('search', '')),
            (int) $request->validated('per_page', 20),
        );

        return response()->json($result);
    }

    public function show(CustomerAdminRequest $request, int $customerId, GetAdminCustomers $customers): JsonResponse
    {
        return response()->json(['data' => $customers->show($customerId)]);
    }
}
