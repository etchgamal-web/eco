<?php

namespace App\Modules\Content\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Application\UseCases\GetPublicContent;
use App\Modules\Content\Application\UseCases\ListPublicContent;
use App\Modules\Content\Application\UseCases\ManageContent;
use App\Modules\Content\Domain\ValueObjects\ContentData;
use App\Modules\Content\Presentation\Http\Requests\ContentReadRequest;
use App\Modules\Content\Presentation\Http\Requests\ContentWriteRequest;
use App\Modules\Content\Presentation\Http\Requests\PublicContentRequest;
use Illuminate\Http\JsonResponse;

final class ContentController extends Controller
{
    public function publicIndex(PublicContentRequest $request, ListPublicContent $list): JsonResponse
    {
        $type = $request->validated('type');
        $page = (int) $request->validated('page', 1);
        $perPage = (int) $request->validated('per_page', 20);

        return response()->json($list->execute($type, $page, $perPage));
    }

    public function publicShow(PublicContentRequest $request, string $slug, GetPublicContent $get): JsonResponse
    {
        return response()->json(['data' => $get->execute($slug)]);
    }

    public function index(ContentReadRequest $request, ManageContent $manage): JsonResponse
    {
        return response()->json(['data' => $manage->list($request->validated())]);
    }

    public function show(ContentReadRequest $request, int $content, ManageContent $manage): JsonResponse
    {
        return response()->json(['data' => $manage->get($content)]);
    }

    public function store(ContentWriteRequest $request, ManageContent $manage): JsonResponse
    {
        return response()->json(['data' => $manage->persist(ContentData::fromArray($request->validated()))], 201);
    }

    public function update(ContentWriteRequest $request, int $content, ManageContent $manage): JsonResponse
    {
        return response()->json(['data' => $manage->persist(ContentData::fromArray($request->validated()), $content)]);
    }

    public function destroy(ContentReadRequest $request, int $content, ManageContent $manage): JsonResponse
    {
        $manage->remove($content);

        return response()->json(null, 204);
    }

    public function publish(ContentReadRequest $request, int $content, ManageContent $manage): JsonResponse
    {
        return response()->json(['data' => $manage->publish($content)]);
    }

    public function unpublish(ContentReadRequest $request, int $content, ManageContent $manage): JsonResponse
    {
        return response()->json(['data' => $manage->unpublish($content)]);
    }

    public function preview(ContentReadRequest $request, int $content, ManageContent $manage): JsonResponse
    {
        return response()->json(['data' => $manage->get($content)]);
    }
}
