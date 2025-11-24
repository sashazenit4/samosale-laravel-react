<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Проверка статусов транзакций каждые 5 минут
Schedule::command('transactions:check-multiple-statuses')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/transaction-statuses.log'));

// Проверка просроченных транзакций каждую минуту
Schedule::command('transactions:check-expired')
    ->everyMinute()
    ->withoutOverlapping();
