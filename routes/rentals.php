<?php

use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('rentals', RentalController::class);

// Дополнительные маршруты для аренд
    Route::post('/rentals/{rental}/complete', [RentalController::class, 'complete'])
        ->name('rentals.complete');

    Route::post('/rentals/{rental}/complete-early', [RentalController::class, 'completeEarly'])
        ->name('rentals.complete-early');

    Route::post('/rentals/{rental}/mark-paid', [RentalController::class, 'markAsPaid'])
        ->name('rentals.mark-paid');

    Route::post('/rentals/calculate-price', [RentalController::class, 'calculatePrice'])
        ->name('rentals.calculate-price');
});
