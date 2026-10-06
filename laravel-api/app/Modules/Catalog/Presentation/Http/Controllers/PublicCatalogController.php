<?php

namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use Illuminate\Http\JsonResponse;

final class PublicCatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json(['data' => $categories]);
    }

    public function brands(): JsonResponse
    {
        $brands = Brand::query()
            ->where('status', 'active')
            ->withCount('products')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json(['data' => $brands]);
    }
}
