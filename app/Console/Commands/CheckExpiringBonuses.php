<?php

namespace App\Console\Commands;

use App\Models\BonusOperation;
use App\Models\Client;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringBonuses extends Command
{
    protected $signature = 'bonuses:check-expiring 
                            {--days=3 : Количество дней до сгорания для проверки}
                            {--notify-managers : Отправить уведомление менеджерам}
                            {--dry-run : Только проверить, без отправки сообщений}';
    
    protected $description = 'Проверяет бонусы, которые скоро сгорят, и отправляет уведомления';

    protected TelegramNotificationService $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle()
    {
        $days = (int)$this->option('days'); // Явное приведение к int
        $notifyManagers = $this->option('notify-managers');
        $dryRun = $this->option('dry-run');

        $this->info("Поиск бонусов, которые сгорят через {$days} дней...");

        // Находим дату сгорания
        $expirationDate = now()->addDays($days);
        
        // Ищем бонусы, которые сгорят через указанное количество дней
        $bonuses = BonusOperation::query()
            ->with(['client'])
            ->where('type', 'accrual')
            ->where('is_burnable', true)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', $expirationDate->toDateString())
            ->whereRaw('used_amount < amount')
            ->get()
            ->filter(function ($bonus) {
                return $bonus->getAvailableAmount() > 0;
            });

        $this->info("Найдено {$bonuses->count()} бонусных операций для уведомления");

        if ($bonuses->isEmpty()) {
            $this->info('Нет бонусов для уведомления');
            return 0;
        }

        // Группируем бонусы по клиентам
        $clientsBonuses = $bonuses->groupBy('client_id');
        
        $totalNotified = 0;
        $managerData = [
            'expiration_date' => $expirationDate->toDateString(),
            'affected_clients' => [],
            'total_bonuses' => 0
        ];

        foreach ($clientsBonuses as $clientId => $clientBonuses) {
            $client = $clientBonuses->first()->client;
            
            if (!$client || !$client->telegram_id) {
                $this->warn("Клиент {$clientId} не имеет telegram_id");
                continue;
            }

            // Суммируем бонусы клиента
            $totalAmount = $clientBonuses->sum(function ($bonus) {
                return $bonus->getAvailableAmount();
            });

            // Формируем детали по каждому бонусу
            $bonusDetails = $clientBonuses->map(function ($bonus) {
                return [
                    'amount' => $bonus->getAvailableAmount(),
                    'expires_at' => $bonus->expires_at->toDateString(),
                    'operation_id' => $bonus->id
                ];
            })->toArray();

            // Формируем сообщение
            $message = $this->telegramService->formatBonusExpirationMessage([
                'user_name' => $client->name,
                'bonus_amount' => $totalAmount,
                'expiration_date' => $expirationDate->toDateString(),
                'bonus_details' => $bonusDetails
            ]);

            // Отправляем сообщение (если не dry-run)
            if (!$dryRun) {
                $sent = $this->telegramService->sendToClient($client->telegram_id, $message);
                
                if ($sent) {
                    $this->info("Отправлено уведомление клиенту {$client->name} (ID: {$client->user_id})");
                    $totalNotified++;
                    
                    // Логируем отправку
                    Log::info('Уведомление о сгорании бонусов отправлено', [
                        'client_id' => $client->user_id,
                        'telegram_id' => $client->telegram_id,
                        'bonus_amount' => $totalAmount,
                        'expiration_date' => $expirationDate->toDateString()
                    ]);
                } else {
                    $this->error("Ошибка отправки клиенту {$client->name}");
                }
            } else {
                $this->line("[DRY RUN] Будет отправлено клиенту {$client->name}: {$totalAmount} бонусов");
            }

            // Собираем данные для менеджеров
            $managerData['affected_clients'][] = [
                'id' => $client->user_id,
                'name' => $client->name,
                'telegram_id' => $client->telegram_id,
                'bonus_amount' => $totalAmount
            ];
            $managerData['total_bonuses'] += $totalAmount;
        }

        // Отправляем уведомление менеджерам
        if ($notifyManagers && !empty($managerData['affected_clients'])) {
            $managerMessage = $this->telegramService->formatManagerBonusExpirationMessage($managerData);
            
            if (!$dryRun) {
                $sent = $this->telegramService->sendToManager($managerMessage);
                if ($sent) {
                    $this->info('Уведомление отправлено менеджерам');
                } else {
                    $this->error('Ошибка отправки уведомления менеджерам');
                }
            } else {
                $this->line("[DRY RUN] Сообщение для менеджеров:");
                $this->line($managerMessage);
            }
        }

        $this->info("Готово! Уведомлений отправлено: {$totalNotified}");
        
        return 0;
    }
}