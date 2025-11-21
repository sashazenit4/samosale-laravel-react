<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->enum('month', [
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ]);
            $table->enum('status', ['paid', 'partially_paid', 'unpaid'])->default('unpaid');
            $table->year('year');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('payment_type', ['cash', 'cashless', 'mixed']);
            $table->foreignId('client_id')->constrained('clients', 'user_id');
            $table->enum('article', ['bike_rental', 'bike_repair']);
            $table->text('purpose')->nullable();
            $table->foreignId('rental_id')->nullable()->constrained('rentals');
            $table->timestamps();

            // Индексы для оптимизации
            $table->index(['year', 'month']);
            $table->index('status');
            $table->index('payment_type');
            $table->index('article');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
