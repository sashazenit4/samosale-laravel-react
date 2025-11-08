<?php
use App\Http\Controllers\Auth\ApiAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [ApiAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/user', [ApiAuthController::class, 'user']);

require __DIR__.'/loyalty.php';
require __DIR__.'/clients.php';
require __DIR__.'/tariffs.php';
require __DIR__.'/bikes.php';
require __DIR__.'/equipment.php';
