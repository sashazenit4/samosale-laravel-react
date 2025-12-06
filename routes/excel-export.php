<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::get('/export', [ExportController::class, 'exportForm'])->name('export.form');
    Route::get('/export/columns/{table}', [ExportController::class, 'getTableColumns'])->name('export.columns');
    Route::post('/export/{table}', [ExportController::class, 'exportTable'])->name('export.table');
});
