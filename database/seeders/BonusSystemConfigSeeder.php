<?php

namespace Database\Seeders;

use App\Models\BonusSystemConfig;
use Illuminate\Database\Seeder;

class BonusSystemConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // Новые конфиги
            [
                'key' => 'bonus_lifetime_days',
                'value' => ['days' => 30],
                'description' => 'Время жизни начисленного бонуса (в днях)'
            ],
            [
                'key' => 'referral_bonus_condition',
                'value' => ['referee_min_spent' => 1000],
                'description' => 'Минимальная сумма, которую должен потратить приглашенный, чтобы пригласивший получил бонус'
            ]
        ];

        foreach ($configs as $config) {
            BonusSystemConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }

        $this->command->info('Bonus system configurations seeded successfully!');
    }
}
