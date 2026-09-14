<?php
namespace App\Modules\Catalog\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\UseCases\ManageProductMedia;
use App\Modules\Catalog\Presentation\Http\Requests\ProductMediaRequest;
use Illuminate\Http\JsonResponse;
final class ProductMediaController extends Controller
{
    public function index(ProductMediaRequest $request, int $product, ManageProductMedia $useCase): JsonResponse { return response()->json(['data' => $useCase->list($product)]); }
    public function store(ProductMediaRequest $request, int $product, ManageProductMedia $useCase): JsonResponse { return response()->json(['data' => $useCase->upload($product, null, $request->file('file'))], 201); }
    public function destroy(ProductMediaRequest $request, int $product, int $media, ManageProductMedia $useCase): JsonResponse { $useCase->remove($product, null, $media); return response()->json(null, 204); }
    public function reorder(ProductMediaRequest $request, int $product, int $media, ManageProductMedia $useCase): JsonResponse { return response()->json(['data' => $useCase->reorder($product, null, $media, (int) $request->validated('sort_order'))]); }
    public function variantIndex(ProductMediaRequest $request, int $product, int $variant, ManageProductMedia $useCase): JsonResponse { return response()->json(['data' => $useCase->list($product, $variant)]); }
    public function variantStore(ProductMediaRequest $request, int $product, int $variant, ManageProductMedia $useCase): JsonResponse { return response()->json(['data' => $useCase->upload($product, $variant, $request->file('file'))], 201); }
    public function variantDestroy(ProductMediaRequest $request, int $product, int $variant, int $media, ManageProductMedia $useCase): JsonResponse { $useCase->remove($product, $variant, $media); return response()->json(null, 204); }
    public function variantReorder(ProductMediaRequest $request, int $product, int $variant, int $media, ManageProductMedia $useCase): JsonResponse { return response()->json(['data' => $useCase->reorder($product, $variant, $media, (int) $request->validated('sort_order'))]); }
}
