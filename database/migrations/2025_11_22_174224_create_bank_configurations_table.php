<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_configurations', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('Название конфигурации');
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');

            // Основные идентификаторы
            $table->string('merchant_id')->comment('ID мерчанта в банке');
            $table->string('account_id')->comment('ID счета в банке');

            // JWT авторизация
            $table->text('jwt_token')->nullable()->comment('JWT токен для авторизации');
            $table->string('customer_code')->nullable()->comment('Код клиента в банке');
            $table->string('bank_code')->nullable()->comment('Код банка');

            // Дополнительные поля из последней миграции
            $table->string('legal_id')->nullable()->comment('ID юридического лица в банке');
            $table->string('brand_name')->nullable()->comment('Название бренда мерчанта');
            $table->string('mcc')->nullable()->comment('MCC код мерчанта');
            $table->string('contact_phone')->nullable()->comment('Контактный телефон');
            $table->string('city')->nullable()->comment('Город');
            $table->string('country_code')->default('RU')->comment('Код страны');

            $table->string('api_version')->default('v1.0');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Индексы
            $table->index('environment');
            $table->index('is_active');
            $table->unique(['environment', 'merchant_id', 'account_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_configurations');
    }
};
