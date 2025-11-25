<?php
use App\Http\Controllers\Auth\ApiAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [ApiAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/user', [ApiAuthController::class, 'user']);

require __DIR__.'/clients.php';
require __DIR__.'/tariffs.php';
require __DIR__.'/bikes.php';
require __DIR__.'/equipment.php';
require __DIR__.'/invites.php';
require __DIR__.'/rentals.php';
require __DIR__.'/payments.php';
require __DIR__.'/bank-configuration.php';
require __DIR__.'/transactions.php';
require __DIR__.'/bonuses.php';
require __DIR__.'/users.php';
require __DIR__.'/bonus-config.php';
