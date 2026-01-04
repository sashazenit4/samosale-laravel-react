<?php
use App\Http\Controllers\NpsSurveyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::get('/nps-surveys/today', [NpsSurveyController::class, 'getTodaySurveys']);
    Route::apiResource('nps-surveys', NpsSurveyController::class);
});
