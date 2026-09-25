<?php

namespace App\Modules\LandingPage\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandingPage\Application\UseCases\CaptureLandingLead;
use App\Modules\LandingPage\Application\UseCases\GetPublishedLandingPage;
use App\Modules\LandingPage\Presentation\Http\Requests\LandingLeadRequest;
use App\Modules\LandingPage\Presentation\Http\Requests\PublicLandingPageRequest;
use Illuminate\Http\JsonResponse;

final class PublicLandingPageController extends Controller
{
    public function show(PublicLandingPageRequest $r, string $slug, GetPublishedLandingPage $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($slug)]);
    }

    public function lead(LandingLeadRequest $r, string $slug, CaptureLandingLead $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($slug, $r->validated(), $r->ip())], 201);
    }
}
