<?php
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('users/{user}/admin', [UserController::class, 'isUserAdmin']);
});
