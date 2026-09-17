<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialCommerce\Application\UseCases\ProcessWebhook;
use App\Modules\SocialCommerce\Presentation\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;

final class SocialWebhookController extends Controller
{
    public function receive(WebhookRequest $request, string $channel, ProcessWebhook $useCase): JsonResponse
    {
        $conversation = $useCase->execute($channel, $request->all(), $request->headers->all(), $request->getContent());

        return response()->json(['received' => true, 'conversation_id' => $conversation->id ?? null]);
    }

    public function verify(WebhookRequest $request, string $channel): JsonResponse
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        if ($mode === 'subscribe' && hash_equals((string) config('services.social.'.$channel.'.verify_token'), (string) $token)) {
            return response()->json((string) $request->query('hub_challenge'));
        }

        return response()->json(['message' => 'Verification failed.'], 403);
    }
}
