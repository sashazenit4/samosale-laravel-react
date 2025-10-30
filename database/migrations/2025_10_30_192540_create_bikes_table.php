<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bikes', function (Blueprint $table) {
            $table->id();
            $table->string('bike_number')->unique();
            $table->string('frame_number')->unique();
            $table->enum('status', ['renting', 'free', 'stolen'])->default('free');
            $table->string('type');
            $table->timestamps();

            $table->index(['bike_number', 'status']);
            $table->index('frame_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bikes');
    }
};
