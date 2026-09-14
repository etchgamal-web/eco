<?php

namespace App\Modules\Payment\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\UseCases\ProcessPaymobWebhook;
use App\Modules\Payment\Presentation\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;

final class PaymobWebhookController extends Controller
{
    public function __invoke(WebhookRequest $request, ProcessPaymobWebhook $process): JsonResponse
    {
        $process->execute($request->all(), (string) $request->query('hmac', $request->header('X-Paymob-Hmac', '')));

        return response()->json(['received' => true]);
    }
}
