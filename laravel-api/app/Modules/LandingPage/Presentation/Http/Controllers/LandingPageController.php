<?php

namespace App\Modules\LandingPage\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandingPage\Application\UseCases\ManageLandingPage;
use App\Modules\LandingPage\Presentation\Http\Requests\LandingPageReadRequest;
use App\Modules\LandingPage\Presentation\Http\Requests\LandingPageRequest;
use Illuminate\Http\JsonResponse;

final class LandingPageController extends Controller
{
    public function index(LandingPageReadRequest $r, ManageLandingPage $u): JsonResponse
    {
        return response()->json(['data' => $u->list($r->validated())]);
    }

    public function store(LandingPageRequest $r, ManageLandingPage $u): JsonResponse
    {
        return response()->json(['data' => $u->persistPage($r->validated())], 201);
    }

    public function update(LandingPageRequest $r, int $page, ManageLandingPage $u): JsonResponse
    {
        return response()->json(['data' => $u->persistPage($r->validated(), $page)]);
    }

    public function destroy(LandingPageReadRequest $r, int $page, ManageLandingPage $u): JsonResponse
    {
        $u->remove($page);

        return response()->noContent();
    }

    public function publish(LandingPageReadRequest $r, int $page, ManageLandingPage $u): JsonResponse
    {
        return response()->json(['data' => $u->publish($page)]);
    }

    public function unpublish(LandingPageReadRequest $r, int $page, ManageLandingPage $u): JsonResponse
    {
        return response()->json(['data' => $u->unpublish($page)]);
    }
}
