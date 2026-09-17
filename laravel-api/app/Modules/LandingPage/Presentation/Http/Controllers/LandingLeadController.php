<?php

namespace App\Modules\LandingPage\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandingPage\Application\UseCases\ManageLandingLead;
use App\Modules\LandingPage\Presentation\Http\Requests\LandingLeadManagementRequest;
use Illuminate\Http\JsonResponse;

final class LandingLeadController extends Controller
{
    public function index(LandingLeadManagementRequest $r, ManageLandingLead $u): JsonResponse
    {
        return response()->json(['data' => $u->list($r->validated())]);
    }

    public function update(LandingLeadManagementRequest $r, int $lead, ManageLandingLead $u): JsonResponse
    {
        return response()->json(['data' => $u->update($lead, $r->validated())]);
    }
}
