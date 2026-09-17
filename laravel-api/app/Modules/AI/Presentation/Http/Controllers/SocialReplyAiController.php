<?php

namespace App\Modules\AI\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AI\Application\UseCases\SuggestSocialReply;
use App\Modules\AI\Presentation\Http\Requests\SocialReplyAiRequest;
use Illuminate\Http\JsonResponse;

final class SocialReplyAiController extends Controller
{
    public function suggestion(SocialReplyAiRequest $r, SuggestSocialReply $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($r->validated())]);
    }
}
