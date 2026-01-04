<?php

namespace App\Http\Controllers;

use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramNotificationController extends Controller
{
    private TelegramNotificationService $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * 1. Уведомление о сгорании бонусов (клиент)
     */
    public function notifyBonusExpiration(Request $request)
    {
        $request->validate([
            'telegram_id' => 'required|string',
            'user_name' => 'required|string',
            'bonus_amount' => 'required|integer|min:1',
            'expiration_date' => 'required|date',
            'bonus_details' => 'sometimes|array'
        ]);

        $message = $this->telegramService->formatBonusExpirationMessage([
            'user_name' => $request->user_name,
            'bonus_amount' => $request->bonus_amount,
            'expiration_date' => $request->expiration_date,
            'bonus_details' => $request->bonus_details ?? []
        ]);

        $success = $this->telegramService->sendToClient($request->telegram_id, $message);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Уведомление отправлено' : 'Ошибка отправки'
        ]);
    }

    /**
     * 2. Уведомление об изменении уровня лояльности
     */
    public function notifyLoyaltyChange(Request $request)
    {
        $request->validate([
            'telegram_id' => 'required|string',
            'user_name' => 'required|string',
            'old_level' => 'required|string',
            'new_level' => 'required|string',
        ]);

        $message = "<b>🎉 Изменение уровня лояльности</b>\n\n"
                 . "Поздравляем, {$request->user_name}!\n\n"
                 . "Ваш уровень лояльности изменился:\n"
                 . "📊 <b>{$request->old_level}</b> → <b>{$request->new_level}</b>\n\n"
                 . "Спасибо, что вы с нами!";

        $success = $this->telegramService->sendToClient($request->telegram_id, $message);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Уведомление отправлено' : 'Ошибка отправки'
        ]);
    }

    /**
     * 3. Уведомление для менеджеров (новый метод)
     */
    public function notifyManagers(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'chat_ids' => 'sometimes|array',
            'chat_ids.*' => 'integer'
        ]);

        if ($request->has('chat_ids')) {
            $results = $this->telegramService->sendToManagers(
                $request->chat_ids,
                $request->message,
                $request->get('parse_mode', 'HTML')
            );
            
            return response()->json([
                'success' => in_array(true, $results, true),
                'results' => $results
            ]);
        } else {
            $success = $this->telegramService->sendToManager(
                $request->message,
                $request->get('parse_mode', 'HTML')
            );
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Уведомление отправлено' : 'Ошибка отправки'
            ]);
        }
    }

    /**
     * 4. Тест отправки сообщения (для отладки)
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'telegram_id' => 'required|string',
            'type' => 'required|in:client,manager'
        ]);

        if ($request->type === 'client') {
            $success = $this->telegramService->sendToClient(
                $request->telegram_id,
                '✅ <b>Тестовое сообщение</b>\nЭто тестовое уведомление от системы бонусов.'
            );
        } else {
            $success = $this->telegramService->sendToManager(
                '🛠 <b>Тест менеджерского бота</b>\nТестовое сообщение от системы уведомлений.'
            );
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Тестовое сообщение отправлено' : 'Ошибка отправки'
        ]);
    }
}