<?php

namespace App\Modules\LandingPage\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandingPage\Application\UseCases\TrackLandingEvent;
use App\Modules\LandingPage\Presentation\Http\Requests\LandingEventRequest;
use App\Modules\LandingPage\Presentation\Http\Requests\LandingLeadManagementRequest;
use Illuminate\Http\JsonResponse;

final class LandingAnalyticsController extends Controller
{
    public function event(LandingEventRequest $r, string $slug, TrackLandingEvent $u): JsonResponse
    {
        return response()->json(['data' => $u->record($slug, $r->validated())], 201);
    }

    public function stats(LandingLeadManagementRequest $r, int $page, TrackLandingEvent $u): JsonResponse
    {
        return response()->json(['data' => $u->stats($page, $r->validated())]);
    }
}
