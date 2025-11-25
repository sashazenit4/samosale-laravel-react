<?php

use App\Http\Controllers\BonusSystemConfigController;
use Illuminate\Support\Facades\Route;

Route::apiResource('bonus-config', BonusSystemConfigController::class);

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::get('bonus-config/business/welcome-bonus', [BonusSystemConfigController::class, 'getWelcomeBonus']);
    Route::get('bonus-config/business/referral-bonus', [BonusSystemConfigController::class, 'getReferralBonus']);
    Route::get('bonus-config/business/payment-percentage', [BonusSystemConfigController::class, 'getPaymentBonusPercentage']);
    Route::get('bonus-config/business/bonus-levels', [BonusSystemConfigController::class, 'getBonusLevels']);
    Route::get('bonus-config/business/client-level', [BonusSystemConfigController::class, 'getClientLevel']);
});
