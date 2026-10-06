<?php

namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\UseCases\Brands\GetPublicBrandBySlug;
use App\Modules\Catalog\Application\UseCases\Brands\ListPublicBrands;
use App\Modules\Catalog\Application\UseCases\Categories\GetPublicCategoryBySlug;
use App\Modules\Catalog\Application\UseCases\Categories\ListPublicCategories;
use App\Modules\Catalog\Presentation\Http\Requests\CatalogActionRequest;
use Illuminate\Http\JsonResponse;

final class PublicCatalogController extends Controller
{
    public function categories(CatalogActionRequest $request, ListPublicCategories $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute()]);
    }

    public function category(CatalogActionRequest $request, string $slug, GetPublicCategoryBySlug $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($slug)]);
    }

    public function brands(CatalogActionRequest $request, ListPublicBrands $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute()]);
    }

    public function brand(CatalogActionRequest $request, string $slug, GetPublicBrandBySlug $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($slug)]);
    }
}
