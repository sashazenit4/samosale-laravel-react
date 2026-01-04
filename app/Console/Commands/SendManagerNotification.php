<?php

namespace App\Console\Commands;

use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class SendManagerNotification extends Command
{
    protected $signature = 'notify:managers 
                            {message : Текст сообщения}
                            {--chat-ids=* : ID чатов менеджеров (если не указано, используется основной)}';
    
    protected $description = 'Отправляет уведомление менеджерам в Telegram';

    protected TelegramNotificationService $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle()
    {
        $message = $this->argument('message');
        $chatIds = $this->option('chat-ids');

        if (empty($chatIds)) {
            $this->info('Отправка сообщения в основной чат менеджеров...');
            $success = $this->telegramService->sendToManager($message);
        } else {
            $this->info("Отправка сообщения в " . count($chatIds) . " чатов...");
            $results = $this->telegramService->sendToManagers($chatIds, $message);
            $success = in_array(true, $results, true);
        }

        if ($success) {
            $this->info('Уведомление отправлено!');
        } else {
            $this->error('Ошибка отправки уведомления');
        }

        return $success ? 0 : 1;
    }
}