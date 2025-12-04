<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Удаляем уникальный индекс сначала (если нужно)
            $table->dropUnique(['telegram_id']);

            // Изменяем колонку на nullable
            $table->bigInteger('telegram_id')->nullable()->change();

            // Добавляем обратно уникальный индекс, но теперь он будет позволять null
            // В MySQL уникальный индекс с null работает по-особому - несколько null значений допускаются
            $table->unique(['telegram_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Удаляем уникальный индекс
            $table->dropUnique(['telegram_id']);

            // Возвращаем колонку в NOT NULL состояние
            // Сначала нужно заполнить null значения, если они есть
            DB::table('clients')
                ->whereNull('telegram_id')
                ->update(['telegram_id' => 0]); // или другое значение по умолчанию

            $table->bigInteger('telegram_id')->nullable(false)->change();

            // Добавляем обратно уникальный индекс
            $table->unique(['telegram_id']);
        });
    }
};
