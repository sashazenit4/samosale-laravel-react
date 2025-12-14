<?php

namespace App\Http\Controllers;

use App\Models\BonusSystemConfig;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\BonusOperation;
use Illuminate\Support\Facades\DB;

class BonusController extends Controller
{
    /**
     * Списание бонусов при создании транзакции (улучшенная версия)
     */
    public function deductBonusForTransaction($transactionId, $deductAmount)
    {
        try {
            DB::beginTransaction();

            $transaction = Transaction::findOrFail($transactionId);
            $client = Client::where('user_id', $transaction->client_id)->firstOrFail();

            // Проверяем бонусный баланс
            if ($client->bonus_balance < $deductAmount) {
                throw new \Exception('Недостаточно бонусов для списания');
            }

            // Проверяем, не списаны ли уже бонусы за эту транзакцию
            $existingDeduction = BonusOperation::where('transaction_id', $transaction->id)
                ->where('type', 'deduction')
                ->exists();

            if ($existingDeduction) {
                throw new \Exception('Бонусы уже списаны за эту транзакцию');
            }

            // Списание бонусов (из bonus_balance)
            $client->bonus_balance -= $deductAmount;
            $client->save();

            // Обновляем транзакцию (если еще не обновлено)
            if ($transaction->bonus_deduct_amount != $deductAmount) {
                $transaction->bonus_deduct_amount = $deductAmount;
                $transaction->save();
            }

            // Создаем запись в истории бонусов
            BonusOperation::create([
                'client_id' => $client->user_id,
                'transaction_id' => $transaction->id,
                'amount' => $deductAmount,
                'type' => 'deduction',
                'description' => 'Списание бонусов для оплаты транзакции',
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $transaction->payment_id,
                    'transaction_amount' => $transaction->amount,
                    'deducted_amount' => $deductAmount,
                    'final_amount_paid' => $transaction->amount
                ]
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Бонусы успешно списаны',
                'bonus_balance' => $client->bonus_balance,
                'real_balance' => $client->balance,
                'deducted_amount' => $deductAmount
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bonus deduction failed', [
                'transaction_id' => $transactionId,
                'deduct_amount' => $deductAmount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при списании бонусов: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Начисление бонусов за оплаченные транзакции (для cron) с учетом уровней
     */
    public function accrueBonusesForPaidTransactions()
    {
        try {
            DB::beginTransaction();

            // Получаем оплаченные транзакции, где не было списания бонусов
            $paidTransactions = Transaction::where('status', 'paid')
                ->where('bonus_deduct_amount', 0)
                ->whereHas('payment', function($query) {
                    // Только для определенных типов платежей (article)
                    $query->whereIn('article', ['rental', 'service', 'product']);
                })
                ->with(['payment'])
                ->get();

            $results = [];
            $accruedCount = 0;

            foreach ($paidTransactions as $transaction) {
                // Проверяем, не начислялись ли уже бонусы за эту транзакцию
                $existingAccrual = BonusOperation::where('transaction_id', $transaction->id)
                    ->where('type', 'accrual')
                    ->exists();

                if ($existingAccrual) {
                    continue;
                }

                // Получаем клиента
                $client = Client::where('user_id', $transaction->client_id)->first();
                if (!$client) {
                    continue;
                }

                // Рассчитываем общую сумму потраченных денег клиентом
                $totalSpent = Payment::where('client_id', $client->user_id)
                    ->where('status', 'paid')
                    ->sum('total_amount');

                // Получаем бонусный процент в зависимости от уровня клиента
                $bonusPercentage = BonusSystemConfig::getClientBonusPercentage($totalSpent);
                $clientLevel = BonusSystemConfig::getClientLevel($totalSpent);

                // Рассчитываем сумму начисления
                $accrualAmount = $transaction->amount * ($bonusPercentage / 100);

                // Начисляем бонусы в bonus_balance
                $client->bonus_balance += $accrualAmount;
                $client->save();

                // Создаем запись в истории бонусов
                BonusOperation::create([
                    'client_id' => $client->user_id,
                    'transaction_id' => $transaction->id,
                    'amount' => $accrualAmount,
                    'type' => 'accrual',
                    'description' => "Начисление бонусов за оплату транзакции (уровень: {$clientLevel['name']})",
                    'metadata' => [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $transaction->payment_id,
                        'transaction_amount' => $transaction->amount,
                        'bonus_percentage' => $bonusPercentage,
                        'client_level' => $clientLevel['level'],
                        'total_spent' => $totalSpent
                    ]
                ]);

                $results[] = [
                    'transaction_id' => $transaction->id,
                    'client_id' => $client->user_id,
                    'accrued_amount' => $accrualAmount,
                    'bonus_percentage' => $bonusPercentage,
                    'client_level' => $clientLevel['name'],
                    'bonus_balance' => $client->bonus_balance,
                    'real_balance' => $client->balance
                ];

                $accruedCount++;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Начисление бонусов завершено. Обработано: $accruedCount транзакций",
                'results' => $results
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Ошибка при начислении бонусов: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получение истории бонусов клиента
     */
    public function getClientBonusHistory($clientId)
    {
        $bonusOperations = BonusOperation::where('client_id', $clientId)
            ->with('transaction')
            ->orderBy('created_at', 'desc')
            ->get();

        $client = Client::where('user_id', $clientId)->first();

        return [
            'success' => true,
            'client_id' => $clientId,
            'bonus_balance' => $client ? $client->bonus_balance : 0,
            'real_balance' => $client ? $client->balance : 0,
            'operations' => $bonusOperations
        ];
    }

    /**
     * Получение текущего баланса клиента
     */
    public function getClientBalance($clientId)
    {
        $client = Client::where('user_id', $clientId)->firstOrFail();

        return [
            'success' => true,
            'client_id' => $clientId,
            'bonus_balance' => $client->bonus_balance,
            'real_balance' => $client->balance
        ];
    }

    /**
     * Ручное начисление бонусов (для админки)
     */
    public function manualAccrual($clientId, $amount, $description = 'Ручное начисление бонусов')
    {
        try {
            DB::beginTransaction();

            $client = Client::where('user_id', $clientId)->firstOrFail();

            // Начисляем бонусы
            $client->bonus_balance += $amount;
            $client->save();

            // Создаем запись в истории бонусов
            BonusOperation::create([
                'client_id' => $client->user_id,
                'amount' => $amount,
                'type' => 'accrual',
                'description' => $description,
                'metadata' => [
                    'operation_type' => 'manual',
                    'admin_id' => auth()->id() ?? null
                ]
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Бонусы успешно начислены',
                'bonus_balance' => $client->bonus_balance
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Ошибка при начислении бонусов: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ручное списание бонусов (для админки)
     */
    public function manualDeduction($clientId, $amount, $description = 'Ручное списание бонусов')
    {
        try {
            DB::beginTransaction();

            $client = Client::where('user_id', $clientId)->firstOrFail();

            if ($client->bonus_balance < $amount) {
                throw new \Exception('Недостаточно бонусов для списания');
            }

            // Списание бонусов
            $client->bonus_balance -= $amount;
            $client->save();

            // Создаем запись в истории бонусов
            BonusOperation::create([
                'client_id' => $client->user_id,
                'amount' => $amount,
                'type' => 'deduction',
                'description' => $description,
                'metadata' => [
                    'operation_type' => 'manual',
                    'admin_id' => auth()->id() ?? null
                ]
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Бонусы успешно списаны',
                'bonus_balance' => $client->bonus_balance
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Ошибка при списании бонусов: ' . $e->getMessage()
            ];
        }
    }
}
