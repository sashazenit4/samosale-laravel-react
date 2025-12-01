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
        Schema::table('bikes', function (Blueprint $table) {
            $table->string('property_1')->nullable()->after('type');
            $table->string('property_2')->nullable()->after('property_1');
            $table->string('property_3')->nullable()->after('property_2');
            $table->string('property_4')->nullable()->after('property_3');
            $table->string('property_5')->nullable()->after('property_4');
            $table->string('property_6')->nullable()->after('property_5');
            $table->string('property_7')->nullable()->after('property_6');
            $table->string('property_8')->nullable()->after('property_7');
            $table->string('property_9')->nullable()->after('property_8');
            $table->string('property_10')->nullable()->after('property_9');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            $table->dropColumn([
                'property_1',
                'property_2',
                'property_3',
                'property_4',
                'property_5',
                'property_6',
                'property_7',
                'property_8',
                'property_9',
                'property_10'
            ]);
        });
    }
};
