<?php

namespace App\Modules\AI\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AI\Application\UseCases\GenerateProductDraft;
use App\Modules\AI\Presentation\Http\Requests\ProductAiRequest;
use Illuminate\Http\JsonResponse;

final class ProductAiController extends Controller
{
    public function draft(ProductAiRequest $r, GenerateProductDraft $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($r->validated())]);
    }
}
