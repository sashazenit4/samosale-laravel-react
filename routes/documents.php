<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RentalContractController;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    // Проверка шаблона
    Route::get('documents/check-template', [RentalContractController::class, 'checkTemplate']);

    // Получить данные аренды для предпросмотра
    Route::get('documents/rentals/{rental}/data', [RentalContractController::class, 'getRentalData']);

    // Скачать договор аренды
    Route::get('documents/rentals/{rental}/download-contract', [RentalContractController::class, 'generateRentalContract']);

    // Сгенерировать договор с кастомными данными
    Route::post('documents/rentals/{rental}/generate-custom-contract', [RentalContractController::class, 'generateCustomContract']);
});
