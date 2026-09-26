<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialCommerce\Application\UseCases\ListInteractions;
use App\Modules\SocialCommerce\Application\UseCases\ReplyToSocialComment;
use App\Modules\SocialCommerce\Presentation\Http\Requests\SocialReadRequest;
use App\Modules\SocialCommerce\Presentation\Http\Requests\SocialReplyRequest;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialConversation;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialInteraction;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialMessage;
use Illuminate\Http\JsonResponse;

final class SocialInteractionController extends Controller
{
    public function index(SocialReadRequest $request, ListInteractions $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($request->validated())]);
    }

    public function summary(SocialReadRequest $request): JsonResponse
    {
        return response()->json(['data' => [
            'orders' => \App\Modules\Order\Infrastructure\Models\CustomerOrder::query()->count(),
            'new_orders' => \App\Modules\Order\Infrastructure\Models\CustomerOrder::query()->whereIn('status', ['pending', 'reviewing', 'confirmed'])->count(),
            'messages' => SocialMessage::query()->count(),
            'comments' => SocialInteraction::query()->where('interaction_type', 'comment')->count(),
            'interactions' => SocialInteraction::query()->count(),
            'conversations' => SocialConversation::query()->count(),
            'open_conversations' => SocialConversation::query()->whereIn('status', ['open', 'pending'])->count(),
            'unanswered_comments' => SocialInteraction::query()->where('interaction_type', 'comment')->whereIn('status', ['open', 'pending', 'new'])->count(),
        ]]);
    }

    public function reply(SocialReplyRequest $request, int $interaction, ReplyToSocialComment $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($interaction, $request->validated('body'), $request->header('Idempotency-Key'))], 201);
    }
}
