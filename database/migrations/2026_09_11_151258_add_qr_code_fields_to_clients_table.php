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
            $table->string('qr_code_id')->nullable()->comment('ID статического QR-кода клиента в банке');
            $table->string('qr_code_url')->nullable()->comment('Payload статического QR-кода для оплаты');
            $table->text('qr_code_image')->nullable()->comment('Изображение статического QR-кода в base64');
            $table->string('qr_code_image_media_type')->nullable()->comment('MIME-тип изображения QR-кода');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'qr_code_id',
                'qr_code_url',
                'qr_code_image',
                'qr_code_image_media_type',
            ]);
        });
    }
};
