<?php

namespace App\Modules\Settings\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Application\UseCases\GetStorefrontTheme;
use App\Modules\Settings\Presentation\Http\Requests\ViewAppearanceRequest;
use Illuminate\Http\JsonResponse;

/**
 * Public, read-only endpoint the storefront uses to know which theme to render.
 */
class PublicAppearanceController extends Controller
{
    public function show(ViewAppearanceRequest $request, GetStorefrontTheme $useCase): JsonResponse
    {
        return response()
            ->json(['data' => ['theme' => $useCase->execute()]])
            ->header('Cache-Control', 'public, max-age=30');
    }
}
