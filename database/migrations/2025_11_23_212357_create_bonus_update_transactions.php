<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Добавляем поле для списания бонусов в транзакции
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('bonus_deduct_amount', 10, 2)->default(0);
        });

        // Добавляем поле для бонусного баланса в клиентов
        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('bonus_balance', 10, 2)->default(0);
        });

        // Создаем таблицу для истории бонусных операций
        Schema::create('bonus_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['accrual', 'deduction']);
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Внешние ключи
            $table->foreign('client_id')->references('user_id')->on('clients');
            $table->foreign('transaction_id')->references('id')->on('transactions');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('bonus_deduct_amount');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('bonus_balance');
        });

        Schema::dropIfExists('bonus_operations');
    }
};
