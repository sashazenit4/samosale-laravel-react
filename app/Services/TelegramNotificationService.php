<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private string $clientBotToken;
    private ?string $managerBotToken;
    private ?int $managerChatId;

    public function __construct()
    {
        $this->clientBotToken = config('services.telegram.client_bot_token');
        $this->managerBotToken = config('services.telegram.manager_bot_token');
        $this->managerChatId = config('services.telegram.manager_chat_id');
    }

    /**
     * Отправка сообщения клиенту
     */
    public function sendToClient(string $telegramId, string $text, string $parseMode = 'HTML'): bool
    {
        return $this->sendMessage($telegramId, $text, $this->clientBotToken, $parseMode);
    }

    /**
     * Отправка сообщения в менеджерский бот
     */
    public function sendToManager(string $text, string $parseMode = 'HTML'): bool
    {
        if (!$this->managerBotToken || !$this->managerChatId) {
            Log::warning('Manager bot credentials not configured');
            return false;
        }

        return $this->sendMessage($this->managerChatId, $text, $this->managerBotToken, $parseMode);
    }

    /**
     * Отправка сообщения нескольким менеджерам
     */
    public function sendToManagers(array $chatIds, string $text, string $parseMode = 'HTML'): array
    {
        $results = [];
        foreach ($chatIds as $chatId) {
            $results[$chatId] = $this->sendMessage($chatId, $text, $this->managerBotToken, $parseMode);
        }
        return $results;
    }

    /**
     * Общий метод отправки сообщения
     */
    private function sendMessage(string $chatId, string $text, string $botToken, string $parseMode): bool
    {
        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Telegram API error', [
                'chat_id' => $chatId,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('Ошибка отправки Telegram сообщения: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'text' => $text
            ]);
            return false;
        }
    }

    /**
     * Форматирование сообщения о сгорании бонусов
     */
    public function formatBonusExpirationMessage(array $data): string
    {
        $expirationDate = \Carbon\Carbon::parse($data['expiration_date'])->format('d.m.Y');
        
        $message = "<b>🔔 Уведомление о бонусах</b>\n\n";
        $message .= "Добрый день, {$data['user_name']}!\n\n";
        $message .= "<b>Важно:</b> До {$expirationDate} сгорят <b>{$data['bonus_amount']}</b> бонусов\n\n";
        
        if (!empty($data['bonus_details'])) {
            $message .= "<b>Детали:</b>\n";
            foreach ($data['bonus_details'] as $detail) {
                $date = \Carbon\Carbon::parse($detail['expires_at'])->format('d.m.Y');
                $message .= "• {$detail['amount']} бонусов (сгорает {$date})\n";
            }
            $message .= "\n";
        }
        
        $message .= "Поторопитесь использовать их до этой даты!\n\n";
        $message .= "Для использования бонусов перейдите в раздел 'Мои бонусы' в вашем личном кабинете.";

        return $message;
    }

    /**
     * Форматирование сообщения для менеджера
     */
    public function formatManagerBonusExpirationMessage(array $data): string
    {
        $expirationDate = \Carbon\Carbon::parse($data['expiration_date'])->format('d.m.Y');
        $clientsCount = count($data['affected_clients']);
        
        $message = "🚨 <b>Уведомление о сгорании бонусов</b>\n\n";
        $message .= "Через 3 дня ({$expirationDate}) сгорят бонусы у клиентов:\n\n";
        
        foreach ($data['affected_clients'] as $client) {
            $message .= "• {$client['name']} (ID: {$client['id']}): {$client['bonus_amount']} бонусов\n";
        }
        
        $message .= "\n📊 <b>Итого:</b> {$clientsCount} клиентов, {$data['total_bonuses']} бонусов\n";
        $message .= "\nРекомендуется напомнить клиентам о возможности использования бонусов.";

        return $message;
    }
}