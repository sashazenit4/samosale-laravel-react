<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bonus_system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Добавляем начальные настройки
        DB::table('bonus_system_configs')->insert([
            [
                'key' => 'welcome_bonus',
                'value' => json_encode(['amount' => 500]),
                'description' => 'Бонус за регистрацию без реферала'
            ],
            [
                'key' => 'referral_bonus',
                'value' => json_encode(['referrer_amount' => 1500, 'referee_amount' => 1500]),
                'description' => 'Бонусы за приглашение (пригласившему и приглашенному)'
            ],
            [
                'key' => 'payment_bonus_percentage',
                'value' => json_encode(['percentage' => 5]),
                'description' => 'Процент начисления бонусов за оплату'
            ],
            [
                'key' => 'bonus_levels',
                'value' => json_encode([
                    [
                        'level' => 1,
                        'name' => 'Новичок',
                        'min_spent' => 0,
                        'bonus_percentage' => 5
                    ],
                    [
                        'level' => 2,
                        'name' => 'Постоянный клиент',
                        'min_spent' => 10000,
                        'bonus_percentage' => 7
                    ],
                    [
                        'level' => 3,
                        'name' => 'VIP клиент',
                        'min_spent' => 50000,
                        'bonus_percentage' => 10
                    ]
                ]),
                'description' => 'Уровни бонусной системы'
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('bonus_system_configs');
    }
};
