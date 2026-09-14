<?php

namespace App\Modules\Shipping\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Application\UseCases\ProcessBostaWebhook;
use App\Modules\Shipping\Application\UseCases\AuthenticateShippingWebhook;
use App\Modules\Shipping\Presentation\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;

final class BostaWebhookController extends Controller
{
    public function __invoke(WebhookRequest $request, ProcessBostaWebhook $process, AuthenticateShippingWebhook $authenticate): JsonResponse
    {
        $header = (string) config('services.bosta.webhook_auth_header', 'Authorization');
        if (! $authenticate->execute($header, (string) $request->header($header, ''))) {
            return response()->json(['message' => 'Invalid Bosta webhook credentials.'], 401);
        }

        $process->execute($request->all());
        return response()->json(['received' => true]);
    }
}
