<?php

use App\Http\Controllers\BikeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::apiResource('bikes', BikeController::class);
    Route::get('bikes/status/{status}', [BikeController::class, 'getByStatus']);
});
