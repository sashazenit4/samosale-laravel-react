<?php
// app/Services/TochkaBankService.php

namespace App\Services;

use App\Models\BankConfiguration;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TochkaBankService
{
    private const QR_CODE_TTL = 15;
    private BankConfiguration $config;

    public function __construct(string $environment = 'sandbox')
    {
        $this->config = BankConfiguration::active()
            ->environment($environment)
            ->firstOrFail();

        if (!$this->config->isTokenValid()) {
            throw new \Exception('Недействительный JWT токен для банковской конфигурации');
        }

        if (!$this->config->isComplete()) {
            throw new \Exception('Банковская конфигурация неполная. Заполните все обязательные поля.');
        }
    }

    /**
     * Создание QR-кода для оплаты
     */
    public function createQrCode(Transaction $transaction): array
    {
        $requestData = [
            'Data' => [
                'merchantId' => $this->config->merchant_id,
                'legalId' => $this->config->legal_id,
                'customerCode' => $this->config->customer_code,
                'amount' => (string) ($transaction->amount * 100), // Перевод в копейки
                'currency' => 'RUB',
                'paymentPurpose' => $transaction->description ?: "Оплата платежа #{$transaction->payment_id}",
                'qrcType' => '02',
                'imageParams' => [
                    'width' => 300,
                    'height' => 300,
                    'mediaType' => 'image/png'
                ],
                'sourceName' => 'bike_rental_system',
                'ttl' => self::QR_CODE_TTL,
            ]
        ];

        Log::info('Tochka Bank QR Code Request', [
            'transaction_id' => $transaction->id,
            'url' => $this->config->getQrCodeUrl(),
            'request_data' => $requestData
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->jwt_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout(config('services.tochka.timeout', 30))
                ->retry(
                    config('services.tochka.retry_times', 3),
                    config('services.tochka.retry_sleep', 100)
                )
                ->post($this->config->getQrCodeUrl(), $requestData);

            $responseData = $response->json();

            Log::info('Tochka Bank QR Code Response', [
                'transaction_id' => $transaction->id,
                'status' => $response->status(),
                'response' => $responseData
            ]);

            if (!$responseData) {
                Log::error('Tochka Bank API returned empty response');
                return [
                    'success' => false,
                    'error' => 'Банк вернул пустой ответ',
                    'code' => 500,
                ];
            }

            // Проверяем структуру ответа
            if ($response->successful()) {
                if (isset($responseData['Data']['qrcId'])) {
                    // Получаем URL из поля payload
                    $qrCodeUrl = $responseData['Data']['payload'] ?? null;

                    // Если есть изображение в base64, можно также его сохранить
                    $imageData = $responseData['Data']['image'] ?? null;

                    Log::info('QR Code created successfully', [
                        'qrcId' => $responseData['Data']['qrcId'],
                        'payload_url' => $qrCodeUrl,
                        'has_image' => !is_null($imageData)
                    ]);

                    return [
                        'success' => true,
                        'qr_code_id' => $responseData['Data']['qrcId'],
                        'qr_code_url' => $qrCodeUrl,
                        'bank_transaction_id' => null, // Пока не известен, будет установлен при проверке статуса
                        'image_data' => $imageData,
                        'response' => $responseData,
                    ];
                } else {
                    Log::error('Tochka Bank API Response missing qrcId', [
                        'response' => $responseData
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Банк не вернул идентификатор QR-кода',
                        'code' => 500,
                    ];
                }
            } else {
                $errorMessage = 'Unknown error from bank API';

                if (isset($responseData['ErrorMessage'])) {
                    $errorMessage = $responseData['ErrorMessage'];
                } elseif (isset($responseData['error'])) {
                    $errorMessage = $responseData['error'];
                } elseif (isset($responseData['message'])) {
                    $errorMessage = $responseData['message'];
                } elseif (is_string($responseData)) {
                    $errorMessage = $responseData;
                }

                Log::error('Tochka Bank API Error', [
                    'transaction_id' => $transaction->id,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'code' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tochka Bank Service Exception', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Проверка статуса платежа по QR-коду
     * Новый метод согласно документации API
     */
    public function checkPaymentStatus(string $qrcId): array
    {
        try {
            // Формируем URL для проверки статуса согласно документации
            $url = $this->config->getBaseUrl() . "/sbp/{$this->config->api_version}/qr-codes/{$qrcId}/payment-status";

            Log::info('Tochka Bank Payment Status Check', [
                'qrc_id' => $qrcId,
                'url' => $url
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->jwt_token,
                'Accept' => 'application/json',
            ])
                ->timeout(config('services.tochka.timeout', 30))
                ->get($url);

            $responseData = $response->json();

            Log::info('Tochka Bank Payment Status Response', [
                'qrc_id' => $qrcId,
                'status' => $response->status(),
                'response' => $responseData
            ]);

            if ($response->successful()) {
                // Обрабатываем ответ согласно документации
                if (isset($responseData['Data'])) {
                    $data = $responseData['Data']['paymentList'][0];
                    return [
                        'success' => true,
                        'status' => $this->mapBankStatus($data['status'] ?? null),
                        'bank_status' => $data['status'] ?? null,
                        'bank_status_message' => $data['message'] ?? null,
                        'bank_transaction_id' => $data['trxId'] ?? null, // trxId из ответа
                        'amount' => isset($data['amount']) ? (float) $data['amount'] / 100 : null,
                        'response' => $responseData,
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Неверный формат ответа от банка',
                        'code' => 500,
                    ];
                }
            } else {
                $errorMessage = $responseData['ErrorMessage'] ??
                    $responseData['error'] ??
                    $responseData['message'] ??
                    'Unknown error from bank API';

                Log::error('Tochka Bank Status Check Error', [
                    'qrc_id' => $qrcId,
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'code' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tochka Bank Status Check Exception', [
                'qrc_id' => $qrcId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Проверка статусов нескольких платежей (batch)
     */
    public function checkMultiplePaymentStatus(array $qrcIds): array
    {
        try {
            // Формируем строку с qrcIds через запятую
            $qrcIdsString = implode(',', $qrcIds);

            // URL для batch проверки статусов
            $url = $this->config->getBaseUrl() . "/sbp/{$this->config->api_version}/qr-codes/payment-status?qrcIds={$qrcIdsString}";

            Log::info('Tochka Bank Multiple Payment Status Check', [
                'qrc_ids' => $qrcIds,
                'url' => $url
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->jwt_token,
                'Accept' => 'application/json',
            ])
                ->timeout(config('services.tochka.timeout', 30))
                ->get($url);

            $responseData = $response->json();

            Log::info('Tochka Bank Multiple Payment Status Response', [
                'qrc_ids' => $qrcIds,
                'status' => $response->status(),
                'response' => $responseData
            ]);

            if ($response->successful()) {
                $results = [];

                if (isset($responseData['Data']['paymentList'])) {
                    foreach ($responseData['Data']['paymentList'] as $payment) {
                        $results[$payment['qrcId']] = [
                            'success' => true,
                            'status' => $this->mapBankStatus($payment['status'] ?? null),
                            'bank_status' => $payment['status'] ?? null,
                            'bank_status_message' => $payment['message'] ?? null,
                            'bank_transaction_id' => $payment['trxId'] ?? null,
                            'code' => $payment['code'] ?? null,
                        ];
                    }
                }

                return [
                    'success' => true,
                    'results' => $results,
                    'response' => $responseData,
                ];
            } else {
                $errorMessage = $responseData['ErrorMessage'] ??
                    $responseData['error'] ??
                    'Unknown error from bank API';

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'code' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tochka Bank Multiple Status Check Exception', [
                'qrc_ids' => $qrcIds,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Маппинг статусов банка на внутренние статусы согласно новой документации
     */
    private function mapBankStatus(?string $bankStatus): string
    {
        return match($bankStatus) {
            'NotStarted', 'Received', 'InProgress' => 'pending',
            'Accepted' => 'completed',
            'Rejected' => 'failed',
            'EXPIRED' => 'expired', // На всякий случай оставляем старые статусы
            'CANCELLED' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Получить информацию о мерчанте
     */
    public function getMerchantInfo(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->jwt_token,
                'Accept' => 'application/json',
            ])
                ->timeout(config('services.tochka.timeout', 30))
                ->get($this->config->getMerchantUrl());

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'merchant' => $responseData['Data'] ?? [],
                    'response' => $responseData,
                ];
            } else {
                $errorMessage = $responseData['ErrorMessage'] ??
                    $responseData['error'] ??
                    'Unknown error';

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'code' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Tochka Bank Merchant Info Exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500,
            ];
        }
    }
}
