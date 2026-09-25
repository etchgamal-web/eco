<?php

namespace App\Modules\Shipping\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Application\UseCases\GetPublicShipmentTracking;
use App\Modules\Shipping\Presentation\Http\Requests\PublicTrackingRequest;
use Illuminate\Http\JsonResponse;

final class PublicTrackingController extends Controller
{
    public function show(PublicTrackingRequest $request, GetPublicShipmentTracking $useCase): JsonResponse
    {
        $data = $useCase->execute((string) $request->validated('tracking_token'));

        if ($data === null) {
            return response()->json(['message' => 'Shipment not found.'], 404);
        }

        return response()->json(['data' => $data]);
    }
}
