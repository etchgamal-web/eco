<?php

namespace App\Modules\Settings\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Infrastructure\Models\Setting;
use Illuminate\Http\JsonResponse;

final class PublicCheckoutSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $setting = Setting::query()->where('key', 'checkout.require_authentication')->first();

        return response()->json([
            'data' => [
                'require_authentication' => $setting?->getTypedValue() ?? true,
            ],
        ]);
    }
}
