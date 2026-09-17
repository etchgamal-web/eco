<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialCommerce\Application\DTOs\ConnectionData;
use App\Modules\SocialCommerce\Application\UseCases\CreateSocialConnection;
use App\Modules\SocialCommerce\Application\UseCases\DeleteSocialConnection;
use App\Modules\SocialCommerce\Application\UseCases\GetSocialConnection;
use App\Modules\SocialCommerce\Application\UseCases\ListConnections;
use App\Modules\SocialCommerce\Application\UseCases\UpdateSocialConnection;
use App\Modules\SocialCommerce\Presentation\Http\Requests\SocialConnectionRequest;
use Illuminate\Http\JsonResponse;

final class SocialConnectionController extends Controller
{
    public function index(SocialConnectionRequest $request, ListConnections $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($request->validated())]);
    }

    public function store(SocialConnectionRequest $request, CreateSocialConnection $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute(ConnectionData::fromArray($request->validated()))], 201);
    }

    public function show(SocialConnectionRequest $request, int $connection, GetSocialConnection $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($connection)]);
    }

    public function update(SocialConnectionRequest $request, int $connection, UpdateSocialConnection $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($connection, ConnectionData::fromArray($request->validated()))]);
    }

    public function destroy(SocialConnectionRequest $request, int $connection, DeleteSocialConnection $useCase): JsonResponse
    {
        $useCase->execute($connection);

        return response()->noContent();
    }
}
