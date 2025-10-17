<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoyaltyController;

Route::get('loyalty/users/{userId}/key', [LoyaltyController::class, 'getKeyByUserId'])
    ->where('userId', '[0-9]+')
    ->name('loyalty.getKeyByUserId');

Route::put('loyalty/users/{userId}/storeKey', [LoyaltyController::class, 'storeKey'])
    ->where('userId', '[0-9]+')
    ->name('loyalty.storeKey');
