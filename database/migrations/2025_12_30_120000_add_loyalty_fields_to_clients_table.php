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
            // Уровень лояльности (используется для расчёта процента начисления бонусов)
            $table->unsignedInteger('loyalty_level')
                ->default(1)
                ->after('is_loyalty_member');

            // Сколько всего потрачено (на основе successful transactions)
            $table->decimal('total_spent', 12, 2)
                ->default(0)
                ->after('loyalty_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['loyalty_level', 'total_spent']);
        });
    }
};
