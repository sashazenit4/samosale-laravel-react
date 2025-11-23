<?php

use App\Http\Controllers\BonusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::post('bonus/accrue-registration/{clientId}', [BonusController::class, 'accrueRegistrationBonus']);
    Route::post('bonus/deduct-for-transaction/{transactionId}', [BonusController::class, 'deductBonusForTransaction']);
    Route::post('bonus/accrue-for-paid-transactions', [BonusController::class, 'accrueBonusesForPaidTransactions']);

    // Методы для получения информации
    Route::get('bonus/history/{clientId}', [BonusController::class, 'getClientBonusHistory']);
    Route::get('bonus/balance/{clientId}', [BonusController::class, 'getClientBalance']);

    // Ручные операции (для админки)
    Route::post('bonus/manual-accrual/{clientId}', [BonusController::class, 'manualAccrual']);
    Route::post('bonus/manual-deduction/{clientId}', [BonusController::class, 'manualDeduction']);
});
