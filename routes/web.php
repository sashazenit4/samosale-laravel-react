<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/clients', function () {
    return Inertia::render('Clients');
});

Route::get('/bikes', function () {
    return Inertia::render('Bikes');
});

Route::get('/payments', function () {
    return Inertia::render('Payments');
});

Route::get('/rents', function () {
    return Inertia::render('Rents');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/clients-web.php';
