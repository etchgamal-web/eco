<?php

namespace App\Modules\Payment\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\UseCases\ProcessKashierWebhook;
use App\Modules\Payment\Presentation\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;

final class KashierWebhookController extends Controller
{
    public function __invoke(WebhookRequest $request, ProcessKashierWebhook $process): JsonResponse
    {
        $process->execute($request->all());

        return response()->json(['received' => true]);
    }
}
