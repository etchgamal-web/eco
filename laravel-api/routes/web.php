<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ready', function () {
    $checks = [
        'database' => false,
        'cache' => false,
        'storage' => is_writable(storage_path()),
    ];

    try {
        DB::select('select 1');
        $checks['database'] = true;
    } catch (\Throwable) {
        // Keep the response generic; dependency details belong in server logs.
    }

    try {
        $key = 'readiness.'.bin2hex(random_bytes(8));
        Cache::put($key, true, 5);
        $checks['cache'] = Cache::get($key) === true;
        Cache::forget($key);
    } catch (\Throwable) {
        // Keep the response generic; dependency details belong in server logs.
    }

    $ready = ! in_array(false, $checks, true);

    return response()->json([
        'status' => $ready ? 'ok' : 'degraded',
        'checks' => $checks,
    ], $ready ? 200 : 503);
});
