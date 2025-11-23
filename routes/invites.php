<?php

use App\Http\Controllers\ReferralInviteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('referral-invites', ReferralInviteController::class);

    Route::get('referral-invites/code/{referralCode}', [ReferralInviteController::class, 'findByCode']);
    Route::get('referral-invites/check/{telegramId}', [ReferralInviteController::class, 'checkByTelegramId']);
    Route::delete('referral-invites/telegram/{telegramId}', [ReferralInviteController::class, 'deleteByTelegramId']);
});
