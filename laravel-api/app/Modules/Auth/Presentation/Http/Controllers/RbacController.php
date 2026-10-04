<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\UseCases\GetRbacMatrix;
use App\Modules\Auth\Application\UseCases\UpdateRolePermissions;
use App\Modules\Auth\Application\UseCases\UpdateRoleStatus;
use App\Modules\Auth\Presentation\Http\Requests\RbacRequest;
use App\Modules\Auth\Presentation\Http\Requests\RoleStatusRequest;
use App\Modules\Staff\Application\UseCases\ListRoleAudit;
use App\Modules\Staff\Presentation\Http\Requests\AuditLogRequest;
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

    public function status(RoleStatusRequest $request, int $roleId, UpdateRoleStatus $status): JsonResponse
    {
        return response()->json(['data' => $status->execute($roleId, (bool) $request->validated('is_active'))]);
    }

    public function audit(AuditLogRequest $request, int $roleId, ListRoleAudit $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($roleId, $request->validated())]);
    }
}
