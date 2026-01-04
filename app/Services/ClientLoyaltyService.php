<?php

namespace App\Services;

use App\Models\BonusSystemConfig;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class ClientLoyaltyService
{
    public function __construct(
        private readonly TelegramNotificationService $telegramService,
    ) {
    }

    /**
     * Пересчитать total_spent и loyalty_level для всех клиентов.
     */
    public function recalculateAll(bool $notify = true, int $chunkSize = 500): array
    {
        $updated = 0;
        $levelChanged = 0;

        Client::query()
            ->select(['user_id', 'telegram_id', 'is_loyalty_member', 'loyalty_level', 'total_spent'])
            ->orderBy('user_id')
            ->chunkById($chunkSize, function ($clients) use (&$updated, &$levelChanged, $notify) {
                foreach ($clients as $client) {
                    $result = $this->recalculateForClient($client, $notify);

                    if ($result['updated']) {
                        $updated++;
                    }
                    if ($result['level_changed']) {
                        $levelChanged++;
                    }
                }
            }, 'user_id');

        return [
            'updated_clients' => $updated,
            'level_changed_clients' => $levelChanged,
        ];
    }

    /**
     * Пересчитать total_spent и loyalty_level для конкретного клиента.
     */
    public function recalculateForClient(Client $client, bool $notify = true): array
    {
        $oldLevel = (int) ($client->loyalty_level ?? 1);
        $oldSpent = (float) ($client->total_spent ?? 0);

        $totalSpent = $this->calculateTotalSpent($client->user_id);
        $totalSpent = max(0, (float) $totalSpent);

        $client->total_spent = $totalSpent;

        $newLevel = $oldLevel;
        $newLevelInfo = null;

        if ($client->is_loyalty_member) {
            $newLevelInfo = BonusSystemConfig::getClientLevel($totalSpent);
            $newLevel = (int) ($newLevelInfo['level'] ?? 1);
            $client->loyalty_level = $newLevel;
        }

        $levelChanged = $client->is_loyalty_member && ($newLevel !== $oldLevel);
        $updated = false;

        if ($client->isDirty(['total_spent', 'loyalty_level'])) {
            $client->save();
            $updated = true;
        }

        if ($notify && $levelChanged) {
            $this->notifyLevelChanged($client, $oldLevel, $newLevel, $totalSpent, $newLevelInfo);
        }

        return [
            'updated' => $updated || ($oldSpent !== $totalSpent),
            'level_changed' => $levelChanged,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'total_spent' => $totalSpent,
        ];
    }

    /**
     * total_spent считаем по успешным (completed) транзакциям.
     *
     * - payment: + (amount - bonus_deduct_amount)
     * - refund:  - (amount - bonus_deduct_amount)
     */
    private function calculateTotalSpent(int $clientId): float
    {
        $total = Transaction::query()
            ->where('client_id', $clientId)
            ->where('status', 'completed')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN type = 'refund' THEN - (amount - bonus_deduct_amount) ELSE (amount - bonus_deduct_amount) END), 0) as total"
            )
            ->value('total');

        return (float) $total;
    }

    private function notifyLevelChanged(Client $client, int $oldLevel, int $newLevel, float $totalSpent, ?array $newLevelInfo = null): void
    {
        if (!$client->telegram_id) {
            return;
        }

        try {
            $oldInfo = BonusSystemConfig::getLevelByNumber($oldLevel);
            $newInfo = $newLevelInfo ?? BonusSystemConfig::getLevelByNumber($newLevel);

            $oldName = $oldInfo['name'] ?? (string) $oldLevel;
            $newName = $newInfo['name'] ?? (string) $newLevel;
            $newPercent = $newInfo['bonus_percentage'] ?? BonusSystemConfig::getPaymentBonusPercentage();

            $direction = $newLevel > $oldLevel ? 'повышен' : 'изменён';

            $message = "<b>🎉 Уровень лояльности {$direction}!</b>\n\n";
            $message .= "Было: <b>{$oldName}</b> (уровень {$oldLevel})\n";
            $message .= "Стало: <b>{$newName}</b> (уровень {$newLevel})\n\n";
            $message .= "Теперь начисление бонусов: <b>{$newPercent}%</b>\n";
            $message .= "Всего потрачено: <b>" . number_format($totalSpent, 2, '.', ' ') . "</b>";

            $this->telegramService->sendToClient((string) $client->telegram_id, $message);
        } catch (\Throwable $e) {
            Log::error('Failed to notify client about loyalty level change', [
                'client_id' => $client->user_id,
                'telegram_id' => $client->telegram_id,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
