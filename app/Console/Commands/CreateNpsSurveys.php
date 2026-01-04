<?php

namespace App\Console\Commands;

use App\Models\NpsSurvey;
use App\Models\Rental;
use App\Models\Payment;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CreateNpsSurveys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nps:create-surveys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create NPS surveys for completed and fully paid rentals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting NPS survey creation...');

        // Получаем все завершённые аренды
        $completedRentals = Rental::whereIn('status', ['completed', 'completed_early', 'cancelled'])
            ->with(['payments', 'client'])
            ->get();

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($completedRentals as $rental) {
            // Проверяем, что для этой аренды ещё не создан опрос
            $existingSurvey = NpsSurvey::where('rental_id', $rental->id)->first();
            if ($existingSurvey) {
                $skippedCount++;
                continue;
            }

            // Получаем все платежи по аренде
            $payments = $rental->payments;

            // Проверяем, что есть платежи
            if ($payments->isEmpty()) {
                $skippedCount++;
                continue;
            }

            // Проверяем, что все платежи полностью оплачены
            $allPaid = $payments->every(function ($payment) {
                return $payment->status === 'paid';
            });

            if (!$allPaid) {
                $skippedCount++;
                continue;
            }

            // Находим дату последнего оплаченного платежа
            $paidPayments = $payments
                ->where('status', 'paid')
                ->whereNotNull('paid_at');

            if ($paidPayments->isEmpty()) {
                $skippedCount++;
                continue;
            }

            $lastPaidAt = $paidPayments->sortByDesc('paid_at')->first()->paid_at;

            if (!$lastPaidAt) {
                $skippedCount++;
                continue;
            }

            // Преобразуем в Carbon, если это строка
            if (is_string($lastPaidAt)) {
                $lastPaidAt = Carbon::parse($lastPaidAt);
            }

            // Проверяем, что прошло 3 дня с момента последней оплаты
            $threeDaysAgo = Carbon::now()->subDays(3);
            if ($lastPaidAt->gt($threeDaysAgo)) {
                $skippedCount++;
                continue;
            }

            // Проверяем, что у клиента не было опроса за последние 21 день
            $twentyOneDaysAgo = Carbon::now()->subDays(21);
            $recentSurvey = NpsSurvey::where('client_id', $rental->client_id)
                ->where('created_at', '>=', $twentyOneDaysAgo)
                ->first();

            if ($recentSurvey) {
                $skippedCount++;
                continue;
            }

            // Создаём опрос
            NpsSurvey::create([
                'client_id' => $rental->client_id,
                'rental_id' => $rental->id,
                'status' => NpsSurvey::STATUS_SCHEDULED,
            ]);

            $createdCount++;
            $this->line("Created NPS survey for rental #{$rental->id} (client: {$rental->client_id})");
        }

        $this->info("NPS survey creation completed. Created: {$createdCount}, Skipped: {$skippedCount}");

        return Command::SUCCESS;
    }
}
