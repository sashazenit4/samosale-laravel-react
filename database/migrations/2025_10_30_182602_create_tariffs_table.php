<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('program');
            $table->integer('power'); // 500, 750, 1000
            $table->decimal('price_month', 10, 2); // оплата сразу за месяц
            $table->decimal('price_week1', 10, 2); // оплата за 1 неделю
            $table->decimal('price_week2', 10, 2); // оплата за 2 неделю
            $table->decimal('price_week3', 10, 2); // оплата за 3 неделю
            $table->decimal('price_week4', 10, 2); // оплата за 4 неделю
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Уникальный индекс чтобы не было дубликатов программа+мощность
            $table->unique(['program', 'power']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
