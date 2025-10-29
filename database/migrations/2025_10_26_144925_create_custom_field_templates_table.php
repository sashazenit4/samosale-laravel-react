<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_custom_field_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('type'); // text, number, date, email, select, etc.
            $table->json('validation_rules')->nullable(); // Дополнительные правила валидации
            $table->json('options')->nullable(); // Для select типа - варианты выбора
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_templates');
    }
};
