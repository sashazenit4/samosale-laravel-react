<?php
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('transactions', TransactionController::class);
    Route::post('transactions/with-bonus', [TransactionController::class, 'storeWithBonusDeduction']);
    Route::get('transactions/telegram/{telegramId}', [TransactionController::class, 'byTelegramId']);
    Route::post('transactions/{transaction}/check-status', [TransactionController::class, 'checkStatus']);
    Route::post('transactions/check-multiple-status', [TransactionController::class, 'checkMultipleStatus']);
    Route::post('transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);

    // Получить доступные фильтры
    Route::get('transactions/export/filters', [TransactionExportController::class, 'getFilters']);

    // Получить статистику
    Route::get('transactions/export/stats', [TransactionExportController::class, 'getStats']);

    // JSON экспорт с фильтрацией
    Route::post('transactions/export', [TransactionExportController::class, 'exportTransactions']);

    // Прямое скачивание
    Route::get('transactions/export/direct', [TransactionExportController::class, 'directExport']);
    Route::post('transactions/export/direct', [TransactionExportController::class, 'directExport']);

    // Скачивание по имени файла
    Route::get('transactions/export/download/{filename}', [TransactionExportController::class, 'downloadFile']);
});
