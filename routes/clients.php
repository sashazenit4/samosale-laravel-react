<?php
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    // Специфичные маршруты ДО resource (чтобы не перекрывались)

    // Маршруты для работы с шаблонами полей
    Route::get('clients/field-templates', [ClientController::class, 'getFieldTemplates']);
    Route::post('clients/validate-field', [ClientController::class, 'validateField']);

    // Маршруты провверки существования пользователей
    Route::post('clients/check/telegram', [ClientController::class, 'checkTelegramId']);
    Route::post('clients/check/phone', [ClientController::class, 'checkPhoneNumber']);

    // Маршруты с {id} ДО resource
    Route::get('clients/telegram/{telegramId}', [ClientController::class, 'showByTelegramId']);

    // Основной resource
    Route::apiResource('clients', ClientController::class);

    // Вложенные маршруты ПОСЛЕ resource
    Route::get('clients/{id}/statistics', [ClientController::class, 'statistics']);
    Route::get('clients/{id}/referrals', [ClientController::class, 'referrals']);
    Route::get('clients/{id}/custom-fields', [ClientController::class, 'getCustomFields']);
    Route::put('clients/{id}/custom-field', [ClientController::class, 'updateCustomField']);
    // Проверка активной аренды
    Route::get('/clients/{client}/check-active-rental', [ClientController::class, 'checkActiveRental'])
        ->name('clients.check-active-rental');

    // Проверка активной аренды по telegram_id
    Route::get('/clients/telegram/{telegramId}/check-active-rental', [ClientController::class, 'checkActiveRentalByTelegram'])
        ->name('clients.telegram.check-active-rental');
});
