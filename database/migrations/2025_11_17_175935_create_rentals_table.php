<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients', 'user_id');
            $table->foreignId('bike_id')->constrained();
            $table->foreignId('tariff_id')->constrained();
            $table->string('battery_capacity');
            $table->integer('batteries_count')->default(1);
            $table->timestamp('start_date');
            $table->timestamp('planned_end_date');
            $table->timestamp('actual_end_date')->nullable();
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('paid_status', ['paid', 'unpaid'])->default('unpaid');
            $table->enum('status', ['active', 'completed', 'completed_early', 'cancelled'])->default('active');
            $table->enum('completion_type', ['bike_change', 'cancellation'])->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['bike_id', 'status']);
            $table->index(['start_date', 'planned_end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rentals');
    }
};
