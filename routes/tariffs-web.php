<?php

use App\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('tariffs', TariffController::class);
    Route::get('/tariffs/power/{power}', [TariffController::class, 'getByPower']);
});
