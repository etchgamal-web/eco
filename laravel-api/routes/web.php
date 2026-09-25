<?php

use App\Modules\Shared\Presentation\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ready', ReadinessController::class);
