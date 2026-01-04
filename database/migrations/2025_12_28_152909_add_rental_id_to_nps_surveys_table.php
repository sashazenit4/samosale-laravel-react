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
        Schema::table('nps_surveys', function (Blueprint $table) {
            $table->foreignId('rental_id')->nullable()->after('client_id')->constrained('rentals')->onDelete('cascade');
            $table->index('rental_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nps_surveys', function (Blueprint $table) {
            $table->dropForeign(['rental_id']);
            $table->dropIndex(['rental_id']);
            $table->dropColumn('rental_id');
        });
    }
};
