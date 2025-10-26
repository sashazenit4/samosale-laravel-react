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
        Schema::create('clients', function (Blueprint $table) {
            $table->id('user_id');
            $table->bigInteger('telegram_id')->nullable(false);
            $table->string('phone_number', 32)->nullable(false);
            $table->string('name', 255)->nullable();
            $table->timestamp('registration_date')->useCurrent();
            $table->string('referral_code', 32)->nullable(false);
            $table->integer('referred_by')->nullable();

            $table->timestamps();

            // Индексы для оптимизации запросов
            $table->unique('telegram_id');
            $table->unique('referral_code');
            $table->index('referred_by');
            $table->index('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

