<?php
namespace App\Console\Commands;

use App\Models\Transaction;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BonusController;
use Illuminate\Console\Command;

class CheckMultipleTransactionStatuses extends Command
{
    protected $signature = 'transactions:check-multiple-statuses';
    protected $description = 'Check multiple transaction statuses in batch and process bonus deductions';

    public function handle()
    {
        $this->info('Starting batch transaction status check...');

        // Получаем транзакции для массовой проверки
        $transactions = Transaction::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('qr_code_id')
            ->where('created_at', '>=', now()->subDays(1))
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No transactions to check.');
            return Command::SUCCESS;
        }

        $this->info("Found {$transactions->count()} transactions for batch check");

        // Используем массовую проверку статусов
        $transactionController = new TransactionController();

        $request = new \Illuminate\Http\Request([
            'transaction_ids' => $transactions->pluck('id')->toArray()
        ]);

        $result = $transactionController->checkMultipleStatus($request);
        $responseData = $result->getData();

        $this->info("Batch check completed: {$responseData->message}");

        // Обрабатываем списание бонусов для завершенных транзакций
        $bonusDeducted = 0;
        $bonusController = new BonusController();

        foreach ($responseData->results ?? [] as $result) {
            if (isset($result->new_status) && $result->new_status === 'completed') {
                $transaction = Transaction::find($result->transaction_id);

                if ($transaction && $transaction->bonus_deduct_amount > 0) {
                    $this->info("Processing bonus deduction for transaction {$transaction->id}, amount: {$transaction->bonus_deduct_amount}");

                    $bonusResult = $bonusController->deductBonusForTransaction(
                        $transaction->id,
                        $transaction->bonus_deduct_amount
                    );

                    if ($bonusResult['success']) {
                        $bonusDeducted++;
                        $this->info("Successfully deducted {$transaction->bonus_deduct_amount} bonuses for transaction {$transaction->id}");
                    } else {
                        $this->error("Failed to deduct bonuses for transaction {$transaction->id}: {$bonusResult['message']}");
                    }
                }
            }
        }
        $bonusController->accrueBonusesForPaidTransactions();

        $this->info("Bonus deductions completed: {$bonusDeducted} transactions");

        return Command::SUCCESS;
    }
}
