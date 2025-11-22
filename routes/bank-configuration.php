<?php
use App\Http\Controllers\BankConfigurationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('bank-configurations', BankConfigurationController::class)->only(['index', 'update']);
    Route::post('bank-configurations/{bankConfiguration}/test-connection', [BankConfigurationController::class, 'testConnection']);
    Route::get('bank-configurations/{bankConfiguration}/accounts', [BankConfigurationController::class, 'getAccounts']);
});
