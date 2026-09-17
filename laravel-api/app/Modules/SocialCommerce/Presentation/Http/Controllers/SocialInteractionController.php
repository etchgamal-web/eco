<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialCommerce\Application\UseCases\ListInteractions;
use App\Modules\SocialCommerce\Application\UseCases\ReplyToSocialComment;
use App\Modules\SocialCommerce\Presentation\Http\Requests\SocialReadRequest;
use App\Modules\SocialCommerce\Presentation\Http\Requests\SocialReplyRequest;
use Illuminate\Http\JsonResponse;

final class SocialInteractionController extends Controller
{
    public function index(SocialReadRequest $request, ListInteractions $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($request->validated())]);
    }

    public function reply(SocialReplyRequest $request, int $interaction, ReplyToSocialComment $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($interaction, $request->validated('body'))], 201);
    }
}
