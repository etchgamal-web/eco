<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialCommerce\Application\UseCases\CreateMessageTemplate;
use App\Modules\SocialCommerce\Application\UseCases\DeleteMessageTemplate;
use App\Modules\SocialCommerce\Application\UseCases\ListMessageTemplates;
use App\Modules\SocialCommerce\Application\UseCases\PreviewMessageTemplate;
use App\Modules\SocialCommerce\Application\UseCases\UpdateMessageTemplate;
use App\Modules\SocialCommerce\Presentation\Http\Requests\MessageTemplateRequest;
use Illuminate\Http\JsonResponse;

final class SocialMessageTemplateController extends Controller
{
    public function index(MessageTemplateRequest $request, ListMessageTemplates $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($request->validated())]);
    }

    public function store(MessageTemplateRequest $request, CreateMessageTemplate $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($request->validated())], 201);
    }

    public function update(MessageTemplateRequest $request, int $template, UpdateMessageTemplate $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($template, $request->validated())]);
    }

    public function destroy(MessageTemplateRequest $request, int $template, DeleteMessageTemplate $useCase): JsonResponse
    {
        $useCase->execute($template);

        return response()->noContent();
    }

    public function preview(MessageTemplateRequest $request, int $template, PreviewMessageTemplate $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($template, $request->validated('values', []))]);
    }
}
