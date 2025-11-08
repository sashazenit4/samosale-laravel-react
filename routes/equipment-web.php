<?php

use App\Http\Controllers\BikeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('equipment', \App\Http\Controllers\EquipmentController::class)->only(['index', 'store', 'update', 'destroy']);
});
