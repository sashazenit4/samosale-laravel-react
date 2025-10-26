<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_custom_client_fields_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_client_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients', 'user_id')->onDelete('cascade');
            $table->string('field_name');
            $table->string('field_type'); // text, number, date, etc.
            $table->text('field_value')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_client_fields');
    }
};
