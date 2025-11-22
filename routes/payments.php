<?php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('payments', PaymentController::class);
    Route::get('payments/stats', [PaymentController::class, 'stats']);
    Route::get('payments/telegram/{telegramId}', [PaymentController::class, 'byTelegramId']);
    Route::get('payments/telegram/{telegramId}/stats', [PaymentController::class, 'statsByTelegramId']);
    Route::get('payments/telegram/{telegramId}/{paymentId}', [PaymentController::class, 'paymentByTelegramId']);
});
