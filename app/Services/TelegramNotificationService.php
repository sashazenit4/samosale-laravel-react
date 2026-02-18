<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private string $clientBotToken;
    private ?string $managerBotToken;
    private ?int $managerChatId;
    private ?array $managerChatIds;

    public function __construct()
    {
        $this->clientBotToken = config('services.telegram.client_bot_token');
        $this->managerBotToken = config('services.telegram.manager_bot_token');
        $this->managerChatId = config('services.telegram.manager_chat_id');
        $this->managerChatIds = config('services.telegram.manager_chat_ids');
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
    public function sendToManagers(string $text, string $parseMode = 'HTML', array $chatIds = []): array
    {
        $results = [];
        if (empty($chatIds)) {
            $chatIds = $this->managerChatIds;
        }

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

    /**
     * Форматирование сообщения клиенту о предстоящем платеже
     */
    public function formatClientPaymentNotification(array $data): string
    {
        $dueDate = \Carbon\Carbon::parse($data['due_date'])->format('d.m.Y');
        $isOverdue = $data['is_overdue'] ?? false;

        $message = "<b>💳 Уведомление о платеже</b>\n\n";

        if ($isOverdue) {
            $message .= "⚠️ <b>Внимание!</b> У вас просрочен платеж.\n\n";
        } else {
            $message .= "Напоминаем, что у вас предстоит платеж.\n\n";
        }

        $message .= "<b>Сумма к оплате:</b> {$data['amount']} ₽\n";
        $message .= "<b>Срок оплаты:</b> {$dueDate}\n";

        if (!empty($data['purpose'])) {
            $message .= "<b>Назначение:</b> {$data['purpose']}\n";
        }

        $message .= "\nПожалуйста, произведите оплату в указанный срок.";

        return $message;
    }

    /**
     * Форматирование сообщения менеджеру о предстоящем платеже
     */
    public function formatManagerPaymentNotification(array $data): string
    {
        $dueDate = \Carbon\Carbon::parse($data['due_date'])->format('d.m.Y');
        $isOverdue = $data['is_overdue'] ?? false;
        $message = '';
        if ($isOverdue) {
            $message .= "⚠️ <b>Платеж просрочен!</b>\n";
        }
        $status = $isOverdue ? '🔴 ПРОСРОЧЕН' : '🟡 Завтра';

        $message .= "{$status} <b>Уведомление о платеже</b>\n";
        $message .= "<b>Клиент:</b>\n";
        $message .= "• Telegram ID: {$data['telegram_id']}\n";
        $message .= "• Телефон: {$data['phone_number']}\n";

        if (!empty($data['full_name'])) {
            $message .= "• ФИО: {$data['full_name']}\n";
        }

        $message .= "<b>Платеж:</b>\n";
        $message .= "• Сумма: {$data['amount']} ₽\n";
        $message .= "• Срок оплаты: {$dueDate}\n";

        if (!empty($data['purpose'])) {
            $message .= "• Назначение: {$data['purpose']}\n\n";
        }

        return $message;
    }

    public function formatRentalExpirationNotification(array $data): string
    {
        $daysWord = $data['days_word'];
        $emoji = $data['is_today'] ? '⏰' : '🔔';
        
        $message = "{$emoji} <b>Напоминание об аренде #{$data['rental_id']}</b>\n\n";
        $message .= "Начало аренды: {$data['start_date']}\n";
        $message .= "Окончание аренды: {$data['end_date']}\n\n";
        
        if ($data['is_today']) {
            $message .= "⚠️ <b>Аренда заканчивается СЕГОДНЯ!</b>\n";
            $message .= "Пожалуйста, продлите аренду в этом боте или подготовьте велосипед к возврату.\n\n";
        } else {
            $message .= "📅 <b>Аренда заканчивается ЗАВТРА.</b>\n";
            $message .= "Если вы хотите продлить аренду, пожалуйста, продлите ее в этом боте.\n\n";
        }
        
        $message .= "С уважением, команда велопроката! 🚲";

        return $message;
    }

    /**
     * Форматировать уведомление об окончании аренды для менеджеров
     */
    public function formatManagerRentalNotification(array $data): string
    {
        $emoji = $data['days_word'] === 'сегодня' ? '⏰' : '⚠️';
        $message = "{$emoji} <b>Аренда #{$data['rental_id']} заканчивается {$data['days_word']}</b>\n";
        $message .= "└────────────────────\n";
        $message .= "👤 <b>Клиент:</b> {$data['full_name']}\n";
        $message .= "☎️ <b>Телефон:</b> {$data['phone_number']}\n";
        $message .= "📅 <b>Окончание:</b> {$data['end_date']}";

        return $message;
    }
}
