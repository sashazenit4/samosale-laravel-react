<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_invites', function (Blueprint $table) {
            $table->id();
            $table->string('referral_code')->unique();
            $table->bigInteger('telegram_id')->unique(); // делаем уникальным, чтобы один telegram_id мог иметь только один инвайт
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_invites');
    }
};
