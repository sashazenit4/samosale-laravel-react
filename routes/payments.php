<?php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentExportController;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('payments', PaymentController::class);
    Route::get('payments/stats', [PaymentController::class, 'stats']);
    Route::get('payments/telegram/{telegramId}', [PaymentController::class, 'byTelegramId']);
    Route::get('payments/telegram/{telegramId}/stats', [PaymentController::class, 'statsByTelegramId']);
    Route::get('payments/telegram/{telegramId}/{paymentId}', [PaymentController::class, 'paymentByTelegramId']);

Route::prefix('payments/export')->group(function () {
    Route::get('filters',   [PaymentExportController::class, 'getFilters']);
    Route::post('direct',    [PaymentExportController::class, 'directExport']);
    Route::post('export',   [PaymentExportController::class, 'exportPayments']);
    Route::get('download/{filename}', [PaymentExportController::class, 'downloadFile']);
    Route::get('stats',     [PaymentExportController::class, 'getStats']);
});
});
