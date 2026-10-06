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
        $payment = $process->execute($request->all(), (string) $request->header('x-kashier-signature', ''));
        if ($payment === null) {
            return response()->json(['received' => true, 'duplicate' => true], 409);
        }

        return response()->json(['received' => true]);
    }
}
