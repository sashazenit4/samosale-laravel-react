<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckUpcomingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-upcoming';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check upcoming and overdue payments and send notifications';

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
        $this->info('Starting payment check...');

        // Получаем все неоплаченные и частично оплаченные платежи
        $payments = Payment::whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['client.customFields'])
            ->get();

        $notifiedCount = 0;
        $skippedCount = 0;

        $managerMessage = '';
        foreach ($payments as $payment) {
            // Вычисляем срок платежа (последний день месяца)
            $dueDate = $this->getPaymentDueDate($payment);

            if (!$dueDate) {
                $skippedCount++;
                continue;
            }

            $now = Carbon::now();
            $todayStart = $now->copy()->startOfDay();

            $dueDateStart = Carbon::parse($dueDate)->startOfDay();
            $dueDateEnd   = Carbon::parse($dueDate)->endOfDay();

            $daysUntilDue = $todayStart->diffInDays($dueDateStart, false);
            $this->info(var_export([
                'now' => $now->toDateTimeString(),
                'todayStart' => $todayStart->toDateTimeString(),
                'dueDateStart' => $dueDateStart->toDateTimeString(),
                'dueDateEnd' => $dueDateEnd->toDateTimeString(),
                'daysUntilDue' => $daysUntilDue,
            ], true));

            $isOverdue = $now->gt($dueDateEnd);       // просрочено только после конца дня
            $isDueTomorrow = ($daysUntilDue == 1);   // срок завтра
            $isDueToday = ($daysUntilDue == 0);      // срок сегодня

            if (!$isOverdue && !$isDueTomorrow && !$isDueToday) {
                $skippedCount++;
                continue;
            }

            $client = $payment->client;

            // Пропускаем, если у клиента нет Telegram ID
            if (!$client || !$client->telegram_id) {
                $skippedCount++;
                continue;
            }

            // Получаем ФИО из custom fields
            $fullName = $this->getClientFullName($client);

            // Формируем данные для сообщений
            $paymentData = [
                'amount' => number_format($payment->total_amount - $payment->paid_amount, 2, '.', ' '),
                'due_date' => $dueDate,
                'is_overdue' => $isOverdue,
                'purpose' => $payment->purpose,
            ];

            $managerData = [
                'telegram_id' => $client->telegram_id ?? 'Не указан',
                'phone_number' => $client->phone_number ?? 'Не указан',
                'full_name' => $fullName,
                'amount' => number_format($payment->total_amount - $payment->paid_amount, 2, '.', ' '),
                'due_date' => $dueDate,
                'is_overdue' => $isOverdue,
                'purpose' => $payment->purpose,
            ];

            // Отправляем уведомление клиенту
            $clientMessage = $this->telegramService->formatClientPaymentNotification($paymentData);
            $clientSent = $this->telegramService->sendToClient($client->telegram_id, $clientMessage);

            // Отправляем уведомление менеджеру
            $managerMessage .= $this->telegramService->formatManagerPaymentNotification($managerData) . PHP_EOL . PHP_EOL;

            if ($clientSent) {
                $notifiedCount++;
                $status = $isOverdue ? 'просрочен' : 'завтра';
                $this->line("Notification sent for payment #{$payment->id} (due {$status})");

                Log::info('Payment notification sent', [
                    'payment_id' => $payment->id,
                    'client_id' => $client->user_id,
                    'is_overdue' => $isOverdue,
                    'due_date' => $dueDate,
                ]);
            } else {
                $this->error("Failed to send notification for payment #{$payment->id}");
            }
        }
        $managerSent = $this->telegramService->sendToManagers($managerMessage);

        if (!$managerSent) {
            $this->error("Failed to send notification manager for all payments");
        }

        $this->info("Payment check completed. Notified: {$notifiedCount}, Skipped: {$skippedCount}");

        return Command::SUCCESS;
    }

    /**
     * Получить срок платежа (последний день месяца)
     */
    private function getPaymentDueDate(Payment $payment): ?string
    {
        return $payment->generated_at->format('Y-m-d');
    }

    /**
     * Получить полное имя клиента из custom fields
     */
    private function getClientFullName($client): string
    {
        if (!$client->relationLoaded('customFields')) {
            $client->load('customFields');
        }

        $lastName = $client->getCustomField('last_name');
        $firstName = $client->getCustomField('first_name');
        $middleName = $client->getCustomField('middle_name');

        $parts = array_filter([$lastName, $firstName, $middleName]);

        return !empty($parts) ? implode(' ', $parts) : ($client->name ?? 'Не указано');
    }
}
