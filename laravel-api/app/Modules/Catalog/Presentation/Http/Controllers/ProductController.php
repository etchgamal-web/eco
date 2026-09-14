<?php
namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Domain\ValueObjects\ProductData;
use App\Modules\Catalog\Domain\ValueObjects\ProductVariantData;
use App\Modules\Catalog\Domain\ValueObjects\ProductListCriteria;
use App\Modules\Catalog\Application\UseCases\Products\CreateProduct;
use App\Modules\Catalog\Application\UseCases\Products\CreateProductVariant;
use App\Modules\Catalog\Application\UseCases\Products\DeleteProduct;
use App\Modules\Catalog\Application\UseCases\Products\DeleteProductVariant;
use App\Modules\Catalog\Application\UseCases\Products\GetProduct;
use App\Modules\Catalog\Application\UseCases\Products\GetProductVariant;
use App\Modules\Catalog\Application\UseCases\Products\ListProducts;
use App\Modules\Catalog\Application\UseCases\Products\ListProductVariants;
use App\Modules\Catalog\Application\UseCases\Products\UpdateProduct;
use App\Modules\Catalog\Application\UseCases\Products\UpdateProductVariant;
use App\Modules\Catalog\Presentation\Http\Requests\CatalogActionRequest;
use App\Modules\Catalog\Presentation\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Presentation\Http\Requests\StoreProductVariantRequest;
use App\Modules\Catalog\Presentation\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Presentation\Http\Requests\UpdateProductVariantRequest;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(CatalogActionRequest $request, ListProducts $useCase): JsonResponse
    {
        $filters = $request->validated();
        $products = $filters === [] ? $useCase->execute() : $useCase->execute(ProductListCriteria::fromArray($filters));
        return response()->json(['data' => $products]);
    }

    public function store(StoreProductRequest $request, CreateProduct $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute(ProductData::fromArray($request->validated()))], 201);
    }

    public function show(CatalogActionRequest $request, int $product, GetProduct $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($product)]);
    }

    public function update(UpdateProductRequest $request, int $product, UpdateProduct $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($product, ProductData::fromArray($request->validated()))]);
    }

    public function destroy(CatalogActionRequest $request, int $product, DeleteProduct $useCase): JsonResponse
    {
        $useCase->execute($product);

        return response()->json(null, 204);
    }

    public function variants(CatalogActionRequest $request, int $product, ListProductVariants $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($product)]);
    }

    public function storeVariant(StoreProductVariantRequest $request, int $product, CreateProductVariant $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($product, ProductVariantData::fromArray($request->validated()))], 201);
    }

    public function showVariant(CatalogActionRequest $request, int $product, int $variant, GetProductVariant $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($product, $variant)]);
    }

    public function updateVariant(UpdateProductVariantRequest $request, int $product, int $variant, UpdateProductVariant $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($product, $variant, ProductVariantData::fromArray($request->validated()))]);
    }

    public function destroyVariant(CatalogActionRequest $request, int $product, int $variant, DeleteProductVariant $useCase): JsonResponse
    {
        $useCase->execute($product, $variant);

        return response()->json(null, 204);
    }
}
