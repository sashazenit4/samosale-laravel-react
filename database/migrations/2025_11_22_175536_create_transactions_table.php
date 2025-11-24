<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments');
            $table->foreignId('client_id')->constrained('clients', 'user_id');
            $table->string('bank_transaction_id')->nullable()->comment('ID транзакции в банке');
            $table->string('qr_code_id')->nullable()->comment('ID QR-кода в банке');
            $table->decimal('amount', 10, 2);
            $table->enum('status', [
                'pending',       // Ожидает оплаты
                'processing',    // В обработке
                'completed',     // Успешно завершена
                'failed',        // Неуспешная
                'expired',       // Просрочена
                'cancelled'      // Отменена
            ])->default('pending'); // ВАЖНО: установите значение по умолчанию
            $table->enum('type', ['payment', 'refund'])->default('payment'); // ВАЖНО: установите значение по умолчанию
            $table->text('description')->nullable();
            $table->json('bank_request')->nullable()->comment('Запрос к банку');
            $table->json('bank_response')->nullable()->comment('Ответ от банка');
            $table->string('qr_code_url')->nullable()->comment('URL QR-кода для оплаты');
            $table->timestamp('expires_at')->nullable()->comment('Время истечения срока оплаты');
            $table->timestamp('paid_at')->nullable()->comment('Фактическое время оплаты');
            $table->timestamps();

            // Индексы для оптимизации
            $table->index('client_id');
            $table->index('payment_id');
            $table->index('status');
            $table->index('bank_transaction_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
