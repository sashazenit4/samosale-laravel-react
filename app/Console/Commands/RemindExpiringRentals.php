<?php

namespace App\Console\Commands;

use App\Models\Rental;
use App\Models\Client;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RemindExpiringRentals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rentals:remind-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to clients about rentals expiring today and tomorrow';

    protected TelegramNotificationService $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting rental expiration reminders check...');

        // Получаем даты сегодня и завтра
        $today = Carbon::today()->startOfDay();
        $tomorrow = Carbon::tomorrow()->startOfDay();
        
        // Получаем активные аренды, которые заканчиваются сегодня или завтра
        $rentals = Rental::active()
            ->whereIn('status', ['active'])
            ->whereNotNull('planned_end_date')
            ->whereDate('planned_end_date', '>=', $today)
            ->whereDate('planned_end_date', '<=', $tomorrow)
            ->with(['client.customFields', 'bike', 'tariff'])
            ->get();

        $notifiedToday = 0;
        $notifiedTomorrow = 0;
        $skippedCount = 0;

        $managerMessage = "📋 Напоминания об окончании аренды:\n\n";

        foreach ($rentals as $rental) {
            $client = $rental->client;

            // Пропускаем, если нет клиента
            if (!$client) {
                $skippedCount++;
                Log::warning('Rental without client', ['rental_id' => $rental->id]);
                continue;
            }

            // Определяем, когда заканчивается аренда
            $endDate = Carbon::parse($rental->planned_end_date);
            $isToday = $endDate->isToday();
            $isTomorrow = $endDate->isTomorrow();
            
            if (!$isToday && !$isTomorrow) {
                $skippedCount++;
                continue;
            }
            
            $daysWord = $isToday ? 'сегодня' : 'завтра';

            // Получаем информацию о велосипеде и тарифе (только для внутренней логики)
            $bikeInfo = $rental->bike ? $rental->bike->name . ' (' . $rental->bike->model . ')' : 'Неизвестный велосипед';
            $tariffInfo = $rental->tariff ? $rental->tariff->name : 'Без тарифа';

            // Получаем информацию об аренде для клиентского сообщения
            $rentalData = [
                'rental_id' => $rental->id,
                'days_word' => $daysWord,
                'is_today' => $isToday,
                'start_date' => Carbon::parse($rental->start_date)->format('d.m.Y'),
                'end_date' => $endDate->format('d.m.Y'),
                'end_time' => $endDate->format('H:i'),
            ];

            // Получаем ФИО клиента
            $fullName = $this->getClientFullName($client);
            $telegramId = $this->getClientTelegramId($client);

            // Пропускаем, если у клиента нет Telegram ID
            if (!$telegramId) {
                $skippedCount++;
                Log::info('Client without Telegram ID', [
                    'client_id' => $client->id,
                    'rental_id' => $rental->id
                ]);
                continue;
            }

            // Формируем данные для менеджера
            $managerData = [
                'phone_number' => $client->phone_number ?? 'Не указан',
                'full_name' => $fullName,
                'end_date' => $endDate->format('d.m.Y H:i'),
                'days_word' => $daysWord,
                'rental_id' => $rental->id,
            ];

            // Отправляем уведомление клиенту
            $clientMessage = $this->telegramService->formatRentalExpirationNotification($rentalData);
            $clientSent = $this->telegramService->sendToClient($telegramId, $clientMessage);

            // Добавляем информацию для менеджера
            $managerMessage .= $this->telegramService->formatManagerRentalNotification($managerData) . PHP_EOL . PHP_EOL;

            if ($clientSent) {
                if ($isToday) {
                    $notifiedToday++;
                    $this->line("⏰ [СЕГОДНЯ] Уведомление отправлено для аренды #{$rental->id}");
                } else {
                    $notifiedTomorrow++;
                    $this->line("🔔 [ЗАВТРА] Уведомление отправлено для аренды #{$rental->id}");
                }

                Log::info('Rental expiration reminder sent', [
                    'rental_id' => $rental->id,
                    'client_id' => $client->id,
                    'planned_end_date' => $rental->planned_end_date,
                    'reminder_type' => $daysWord,
                    'telegram_id' => $telegramId,
                ]);
            } else {
                $this->error("Не удалось отправить уведомление для аренды #{$rental->id}");
                Log::error('Failed to send rental reminder', [
                    'rental_id' => $rental->id,
                    'client_id' => $client->id,
                    'telegram_id' => $telegramId,
                ]);
            }
        }

        // Отправляем сводку менеджерам
        if ($notifiedToday > 0 || $notifiedTomorrow > 0) {
            $totalNotified = $notifiedToday + $notifiedTomorrow;
            $summary = "📊 Итог: {$totalNotified} уведомлений отправлено\n";
            $summary .= "• Сегодня: {$notifiedToday}\n";
            $summary .= "• Завтра: {$notifiedTomorrow}\n\n";
            
            $managerMessage = $summary . $managerMessage;
            
            $managerSent = $this->telegramService->sendToManagers($managerMessage);
            
            if (!$managerSent) {
                $this->error("Не удалось отправить сводку менеджерам");
                Log::error('Failed to send summary to managers');
            } else {
                $this->info("Сводка отправлена менеджерам");
            }
        }

        $this->info("Проверка аренд завершена.");
        $this->info("Уведомления отправлены: Сегодня - {$notifiedToday}, Завтра - {$notifiedTomorrow}");
        $this->info("Пропущено: {$skippedCount}");

        return Command::SUCCESS;
    }

    /**
     * Получить полное имя клиента из custom fields
     */
    private function getClientFullName(Client $client): string
    {
        if (!$client->relationLoaded('customFields')) {
            $client->load('customFields');
        }

        $lastName = $client->getCustomField('last_name');
        $firstName = $client->getCustomField('first_name');
        $middleName = $client->getCustomField('middle_name');

        $parts = array_filter([$lastName, $firstName, $middleName]);

        return !empty($parts) ? implode(' ', $parts) : ($client->name ?? 'Клиент #' . $client->id);
    }

    /**
     * Получить Telegram ID клиента
     */
    private function getClientTelegramId(Client $client): ?string
    {
        return $client->telegram_id ?? null;
    }
}