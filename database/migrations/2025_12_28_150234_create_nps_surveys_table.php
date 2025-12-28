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
        Schema::create('nps_surveys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('score')->nullable();
            $table->enum('status', ['scheduled', 'sent', 'completed'])->default('scheduled');
            $table->timestamps();

            $table->foreign('client_id')
                ->references('user_id')
                ->on('clients')
                ->onDelete('cascade');

            $table->index('client_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nps_surveys');
    }
};
