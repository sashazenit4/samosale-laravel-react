<?php
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('transactions', TransactionController::class);
    Route::get('transactions/telegram/{telegramId}', [TransactionController::class, 'byTelegramId']);
    Route::post('transactions/{transaction}/check-status', [TransactionController::class, 'checkStatus']);
    Route::post('transactions/check-multiple-status', [TransactionController::class, 'checkMultipleStatus']);
    Route::post('transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);
});
