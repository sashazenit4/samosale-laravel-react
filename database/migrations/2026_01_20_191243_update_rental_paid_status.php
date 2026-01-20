<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Затем изменим тип поля ENUM
        DB::statement("ALTER TABLE rentals MODIFY COLUMN paid_status ENUM('unpaid', 'partially_paid', 'paid') DEFAULT 'unpaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Вернем старый ENUM
        DB::statement("ALTER TABLE rentals MODIFY COLUMN paid_status ENUM('paid', 'unpaid') DEFAULT 'unpaid'");
    }
};
