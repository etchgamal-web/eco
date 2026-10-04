<?php

namespace App\Modules\Integration\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integration\Application\UseCases\ListIntegrationEvents;
use App\Modules\Integration\Application\UseCases\RetryIntegrationEvent;
use App\Modules\Integration\Presentation\Http\Requests\IntegrationEventRequest;
use Illuminate\Http\JsonResponse;

final class IntegrationEventController extends Controller
{
    public function index(IntegrationEventRequest $request, ListIntegrationEvents $events): JsonResponse { return response()->json(['data' => $events->execute($request->validated())]); }
    public function retry(IntegrationEventRequest $request, string $source, int $id, RetryIntegrationEvent $retry): JsonResponse { return response()->json(['data' => $retry->execute($source, $id)]); }
}
