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

        foreach ($payments as $payment) {
            // Вычисляем срок платежа (последний день месяца)
            $dueDate = $this->getPaymentDueDate($payment);
            
            if (!$dueDate) {
                $skippedCount++;
                continue;
            }

            $now = Carbon::now()->startOfDay();
            $dueDateStart = Carbon::parse($dueDate)->startOfDay();
            $daysUntilDue = $now->diffInDays($dueDateStart, false);

            // Проверяем, что до срока остался 1 день или срок просрочен
            $isOverdue = $dueDateStart->lt($now);
            $isDueTomorrow = $daysUntilDue === 1;

            if (!$isOverdue && !$isDueTomorrow) {
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
            $managerMessage = $this->telegramService->formatManagerPaymentNotification($managerData);
            $managerSent = $this->telegramService->sendToManager($managerMessage);

            if ($clientSent || $managerSent) {
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

        $this->info("Payment check completed. Notified: {$notifiedCount}, Skipped: {$skippedCount}");

        return Command::SUCCESS;
    }

    /**
     * Получить срок платежа (последний день месяца)
     */
    private function getPaymentDueDate(Payment $payment): ?string
    {
        $monthMap = [
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'november' => 11,
            'december' => 12,
        ];

        if (!isset($monthMap[$payment->month]) || !$payment->year) {
            return null;
        }

        $month = $monthMap[$payment->month];
        $year = $payment->year;

        // Получаем последний день месяца
        $lastDay = Carbon::create($year, $month, 1)->endOfMonth();

        return $lastDay->format('Y-m-d');
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
