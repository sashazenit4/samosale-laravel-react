<?php

use App\Http\Controllers\BikeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('bikes', BikeController::class);
    Route::get('bikes/status/{status}', [BikeController::class, 'getByStatus']);
});
