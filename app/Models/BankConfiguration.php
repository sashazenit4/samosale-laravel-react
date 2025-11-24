<?php
// app/Models/BankConfiguration.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class BankConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'environment',
        'legal_id',
        'merchant_id',
        'account_id',
        'api_version',
        'jwt_token',
        'customer_code',
        'bank_code',
        'brand_name',
        'mcc',
        'contact_phone',
        'city',
        'country_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope для активных конфигураций
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для окружения
     */
    public function scopeEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Получить базовый URL для API
     */
    public function getBaseUrl(): string
    {
        $config = config("services.tochka.{$this->environment}");

        // ДОБАВЬТЕ ПРОВЕРКУ НА NULL
        if (is_null($config)) {
            Log::error('Bank configuration not found in config/services.php', [
                'environment' => $this->environment,
                'available_configs' => config('services.tochka')
            ]);
            throw new \Exception("Конфигурация для окружения '{$this->environment}' не найдена в config/services.php");
        }

        return $config['base_url'] ?? '';
    }

    /**
     * Получить полный URL для создания QR-кода
     */
    public function getQrCodeUrl(): string
    {
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/sbp/{$this->api_version}/qr-code/merchant/{$this->merchant_id}/{$this->account_id}";
    }

    /**
     * Получить URL для проверки статуса QR-кода
     */
    public function getQrCodeStatusUrl(string $qrcId): string
    {
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/sbp/{$this->api_version}/qr-codes/{$qrcId}/payment-status";
    }

    /**
     * Получить URL для получения информации о мерчанте
     */
    public function getMerchantUrl(): string
    {
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/sbp/{$this->api_version}/merchant/{$this->merchant_id}";
    }

    /**
     * Получить URL для получения информации о юридическом лице
     */
    public function getLegalEntityUrl(): string
    {
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/sbp/{$this->api_version}/legal-entity/{$this->legal_id}";
    }

    /**
     * Проверить, действителен ли JWT токен
     */
    public function isTokenValid(): bool
    {
        if (!$this->jwt_token) {
            return false;
        }

        // Базовая проверка формата JWT
        return preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/', $this->jwt_token);
    }

    /**
     * Проверить, заполнены ли все обязательные поля
     */
    public function isComplete(): bool
    {
        return !empty($this->legal_id) &&
            !empty($this->merchant_id) &&
            !empty($this->account_id) &&
            !empty($this->jwt_token) &&
            !empty($this->customer_code);
    }

    /**
     * Получить данные мерчанта для регистрации
     */
    public function getMerchantData(): array
    {
        return [
            'customerCode' => $this->customer_code,
            'legalId' => $this->legal_id,
            'address' => $this->city,
            'city' => $this->city,
            'countryCode' => $this->country_code,
            'countrySubDivisionCode' => $this->bank_code,
            'zipCode' => '443000',
            'brandName' => $this->brand_name ?: 'Велобайк',
            'capabilities' => '111',
            'contactPhoneNumber' => $this->contact_phone,
            'mcc' => $this->mcc ?: '5940',
        ];
    }

    /**
     * Получить URL для проверки статуса платежа
     */
    public function getPaymentStatusUrl(string $qrcId): string
    {
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/sbp/{$this->api_version}/qr-codes/{$qrcId}/payment-status";
    }

    /**
     * Получить URL для массовой проверки статусов
     */
    public function getMultiplePaymentStatusUrl(): string
    {
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/sbp/{$this->api_version}/qr-codes/payment-status";
    }
}
