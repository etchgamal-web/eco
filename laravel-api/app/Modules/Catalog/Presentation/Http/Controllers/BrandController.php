<?php
namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Domain\ValueObjects\BrandData;
use App\Modules\Catalog\Application\UseCases\Brands\CreateBrand;
use App\Modules\Catalog\Application\UseCases\Brands\DeleteBrand;
use App\Modules\Catalog\Application\UseCases\Brands\GetBrand;
use App\Modules\Catalog\Application\UseCases\Brands\ListBrands;
use App\Modules\Catalog\Application\UseCases\Brands\UpdateBrand;
use App\Modules\Catalog\Presentation\Http\Requests\CatalogActionRequest;
use App\Modules\Catalog\Presentation\Http\Requests\StoreBrandRequest;
use App\Modules\Catalog\Presentation\Http\Requests\UpdateBrandRequest;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(CatalogActionRequest $request, ListBrands $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute()]);
    }

    public function store(StoreBrandRequest $request, CreateBrand $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute(BrandData::fromArray($request->validated()))], 201);
    }

    public function show(CatalogActionRequest $request, int $id, GetBrand $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($id)]);
    }

    public function update(UpdateBrandRequest $request, int $id, UpdateBrand $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($id, BrandData::fromArray($request->validated()))]);
    }

    public function destroy(CatalogActionRequest $request, int $id, DeleteBrand $useCase): JsonResponse
    {
        $useCase->execute($id);

        return response()->json(null, 204);
    }
}
