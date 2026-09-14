<?php
namespace App\Modules\Catalog\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\UseCases\ManageProductReviews;
use App\Modules\Catalog\Presentation\Http\Requests\ProductReviewRequest;
use Illuminate\Http\JsonResponse;
final class ProductReviewController extends Controller
{
    public function index(ProductReviewRequest $request, int $product, ManageProductReviews $useCase): JsonResponse { return response()->json(['data' => $useCase->approved($product), 'meta' => $useCase->summary($product)]); }
    public function store(ProductReviewRequest $request, int $product, ManageProductReviews $useCase): JsonResponse { return response()->json(['data' => $useCase->submit((int) $request->user()->id, $product, $request->validated())], 201); }
    public function adminIndex(ProductReviewRequest $request, ManageProductReviews $useCase): JsonResponse { return response()->json(['data' => $useCase->all()]); }
    public function moderate(ProductReviewRequest $request, int $review, ManageProductReviews $useCase): JsonResponse { return response()->json(['data' => $useCase->moderate($review, $request->validated('status'))]); }
}
