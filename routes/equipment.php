<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('equipment', \App\Http\Controllers\EquipmentController::class);
    Route::get('equipment/status/{status}', [\App\Http\Controllers\EquipmentController::class, 'getByStatus']);
});
