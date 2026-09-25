<?php

namespace App\Modules\Shared\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Application\Health\ReadinessChecker;
use Illuminate\Http\JsonResponse;

final class ReadinessController extends Controller
{
    public function __invoke(ReadinessChecker $checker): JsonResponse
    {
        $result = $checker->check();
        $ready = $result['ready'];

        return response()->json([
            'status' => $ready ? 'ok' : 'degraded',
            'checks' => $result['checks'],
        ], $ready ? 200 : 503);
    }
}
