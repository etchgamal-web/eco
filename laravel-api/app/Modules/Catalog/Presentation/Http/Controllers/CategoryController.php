<?php
namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Domain\ValueObjects\CategoryData;
use App\Modules\Catalog\Application\UseCases\Categories\CreateCategory;
use App\Modules\Catalog\Application\UseCases\Categories\DeleteCategory;
use App\Modules\Catalog\Application\UseCases\Categories\GetCategory;
use App\Modules\Catalog\Application\UseCases\Categories\ListCategories;
use App\Modules\Catalog\Application\UseCases\Categories\UpdateCategory;
use App\Modules\Catalog\Presentation\Http\Requests\CatalogActionRequest;
use App\Modules\Catalog\Presentation\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Presentation\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(CatalogActionRequest $request, ListCategories $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute()]);
    }

    public function store(StoreCategoryRequest $request, CreateCategory $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute(CategoryData::fromArray($request->validated()))], 201);
    }

    public function show(CatalogActionRequest $request, int $id, GetCategory $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($id)]);
    }

    public function update(UpdateCategoryRequest $request, int $id, UpdateCategory $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($id, CategoryData::fromArray($request->validated()))]);
    }

    public function destroy(CatalogActionRequest $request, int $id, DeleteCategory $useCase): JsonResponse
    {
        $useCase->execute($id);

        return response()->json(null, 204);
    }
}
