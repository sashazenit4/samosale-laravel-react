<?php
// database/seeders/BankConfigurationSeeder.php

namespace Database\Seeders;

use App\Models\BankConfiguration;
use Illuminate\Database\Seeder;

class BankConfigurationSeeder extends Seeder
{
    public function run()
    {
        // Sandbox конфигурация с полным набором данных
        BankConfiguration::create([
            'name' => 'Sandbox Configuration',
            'environment' => 'sandbox',
            'legal_id' => 'LA0000008089',
            'merchant_id' => 'MA0000086825',
            'account_id' => '12345810901234567890/044525104',
            'api_version' => 'v1.0',
            'jwt_token' => 'sandbox.jwt.token',
            'customer_code' => '1234567ab',
            'bank_code' => '044525104',
            'brand_name' => 'Велобайк',
            'mcc' => '5940',
            'contact_phone' => '+79991234567',
            'city' => 'Самара',
            'country_code' => 'RU',
            'is_active' => true,
        ]);

        // Production конфигурация
        BankConfiguration::create([
            'name' => 'Production Configuration',
            'environment' => 'production',
            'legal_id' => env('TOCHKA_PRODUCTION_LEGAL_ID'),
            'merchant_id' => env('TOCHKA_PRODUCTION_MERCHANT_ID'),
            'account_id' => env('TOCHKA_PRODUCTION_ACCOUNT_ID'),
            'api_version' => 'v1.0',
            'jwt_token' => env('TOCHKA_PRODUCTION_JWT_TOKEN'),
            'customer_code' => env('TOCHKA_PRODUCTION_CUSTOMER_CODE'),
            'bank_code' => env('TOCHKA_PRODUCTION_BANK_CODE'),
            'brand_name' => env('TOCHKA_PRODUCTION_BRAND_NAME', 'Велобайк'),
            'mcc' => env('TOCHKA_PRODUCTION_MCC', '5940'),
            'contact_phone' => env('TOCHKA_PRODUCTION_CONTACT_PHONE'),
            'city' => env('TOCHKA_PRODUCTION_CITY', 'Самара'),
            'country_code' => 'RU',
            'is_active' => env('TOCHKA_PRODUCTION_ACTIVE', false),
        ]);
    }
}
