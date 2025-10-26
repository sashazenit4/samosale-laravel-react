<?php
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::apiResource('clients', ClientController::class);

Route::get('clients/telegram/{telegramId}', [ClientController::class, 'showByTelegramId']);
Route::get('clients/{id}/statistics', [ClientController::class, 'statistics']);
Route::get('clients/{id}/referrals', [ClientController::class, 'referrals']);
Route::post('clients/check/telegram', [ClientController::class, 'checkTelegramId']);
Route::post('clients/check/phone', [ClientController::class, 'checkPhoneNumber']);
Route::get('clients/{id}/custom-fields', [ClientController::class, 'getCustomFields']);
Route::put('clients/{id}/custom-field', [ClientController::class, 'updateCustomField']);

// Маршруты для работы с шаблонами полей
Route::get('clients/field-templates', [ClientController::class, 'getFieldTemplates']);
Route::post('clients/validate-field', [ClientController::class, 'validateField']);
