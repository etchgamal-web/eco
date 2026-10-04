<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\UseCases\GetRbacMatrix;
use App\Modules\Auth\Application\UseCases\UpdateRolePermissions;
use App\Modules\Auth\Presentation\Http\Requests\RbacRequest;
use Illuminate\Http\JsonResponse;

final class RbacController extends Controller
{
    public function index(RbacRequest $request, GetRbacMatrix $matrix): JsonResponse
    {
        return response()->json(['data' => $matrix->execute()]);
    }

    public function update(RbacRequest $request, int $roleId, UpdateRolePermissions $permissions): JsonResponse
    {
        return response()->json(['data' => $permissions->execute($roleId, $request->validated('permissions'))]);
    }
}
