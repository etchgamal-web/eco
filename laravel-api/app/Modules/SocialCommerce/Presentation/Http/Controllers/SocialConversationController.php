<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialCommerce\Application\UseCases\GetSocialConversation;
use App\Modules\SocialCommerce\Application\UseCases\ListConversationMessages;
use App\Modules\SocialCommerce\Application\UseCases\SendConversationMessage;
use App\Modules\SocialCommerce\Application\UseCases\SetConversationMode;
use App\Modules\SocialCommerce\Presentation\Http\Requests\ConversationRequest;
use Illuminate\Http\JsonResponse;

final class SocialConversationController extends Controller
{
    public function show(ConversationRequest $request, int $conversation, GetSocialConversation $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($conversation)]);
    }

    public function messages(ConversationRequest $request, int $conversation, ListConversationMessages $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($conversation)]);
    }

    public function send(ConversationRequest $request, int $conversation, SendConversationMessage $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($conversation, $request->validated('body'))], 201);
    }

    public function pause(ConversationRequest $request, int $conversation, SetConversationMode $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($conversation, 'paused')]);
    }

    public function resume(ConversationRequest $request, int $conversation, SetConversationMode $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($conversation, 'automated')]);
    }

    public function manual(ConversationRequest $request, int $conversation, SetConversationMode $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($conversation, 'manual')]);
    }
}
