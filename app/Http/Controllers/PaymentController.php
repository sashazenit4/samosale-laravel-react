<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\TelegramPaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Payment::with(['client', 'rental']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтрация по году
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Фильтрация по месяцу
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        // Фильтрация по клиенту
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Фильтрация по типу оплаты
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Фильтрация по статье
        if ($request->has('article')) {
            $query->where('article', $request->article);
        }

        // Сортировка
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $payments = $query->paginate($request->get('per_page', 15));

        return PaymentResource::collection($payments);
    }

    /**
     * Store a newly created payment.
     */
    public function store(StorePaymentRequest $request): PaymentResource
    {
        $payment = Payment::create($request->validated());

        return new PaymentResource($payment->load(['client', 'rental']));
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource($payment->load(['client', 'rental']));
    }

    /**
     * Update the specified payment.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): PaymentResource
    {
        $payment->update($request->validated());

        return new PaymentResource($payment->load(['client', 'rental']));
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json([
            'message' => 'Платеж успешно удален.'
        ]);
    }

    /**
     * Получить статистику по платежам
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = Payment::selectRaw('
            COUNT(*) as total_count,
            SUM(total_amount) as total_amount,
            SUM(paid_amount) as total_paid,
            COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_count,
            COUNT(CASE WHEN status = "partially_paid" THEN 1 END) as partially_paid_count,
            COUNT(CASE WHEN status = "unpaid" THEN 1 END) as unpaid_count
        ')->when($request->has('year'), function ($query) use ($request) {
            return $query->where('year', $request->year);
        })->first();

        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Display payments by client's telegram_id
     */
    public function byTelegramId(TelegramPaymentRequest $request, $telegramId): AnonymousResourceCollection
    {
        // Валидация telegram_id
        $request->merge(['telegram_id' => $telegramId]);
        $request->validate([
            'telegram_id' => 'required|integer|exists:clients,telegram_id',
        ]);

        // Находим клиента по telegram_id
        $client = Client::where('telegram_id', $telegramId)->firstOrFail();

        $query = Payment::with(['client', 'rental'])
            ->where('client_id', $client->user_id);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтрация по году
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Фильтрация по месяцу
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        // Фильтрация по типу оплаты
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Фильтрация по статье
        if ($request->has('article')) {
            $query->where('article', $request->article);
        }

        // Фильтрация по периоду (начальная и конечная дата)
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('generated_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Сортировка по умолчанию - сначала новые платежи
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $payments = $query->paginate($request->get('per_page', 15));

        return PaymentResource::collection($payments);
    }

    /**
     * Display payment statistics by client's telegram_id
     */
    public function statsByTelegramId(TelegramPaymentRequest $request, $telegramId): JsonResponse
    {
        // Валидация telegram_id
        $request->merge(['telegram_id' => $telegramId]);
        $request->validate([
            'telegram_id' => 'required|integer|exists:clients,telegram_id',
        ]);

        // Находим клиента по telegram_id
        $client = Client::where('telegram_id', $telegramId)->firstOrFail();

        $stats = Payment::where('client_id', $client->user_id)
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(total_amount) as total_amount,
                SUM(paid_amount) as total_paid,
                SUM(total_amount - paid_amount) as total_remaining,
                COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = "partially_paid" THEN 1 END) as partially_paid_count,
                COUNT(CASE WHEN status = "unpaid" THEN 1 END) as unpaid_count
            ')
            ->when($request->has('year'), function ($query) use ($request) {
                return $query->where('year', $request->year);
            })
            ->when($request->has('month'), function ($query) use ($request) {
                return $query->where('month', $request->month);
            })
            ->first();

        return response()->json([
            'client' => new ClientResource($client),
            'stats' => [
                'total_count' => (int) ($stats->total_count ?? 0),
                'total_amount' => (float) ($stats->total_amount ?? 0),
                'total_paid' => (float) ($stats->total_paid ?? 0),
                'total_remaining' => (float) ($stats->total_remaining ?? 0),
                'paid_count' => (int) ($stats->paid_count ?? 0),
                'partially_paid_count' => (int) ($stats->partially_paid_count ?? 0),
                'unpaid_count' => (int) ($stats->unpaid_count ?? 0),
            ]
        ]);
    }

    /**
     * Display a specific payment by ID for a client by telegram_id
     */
    public function paymentByTelegramId($telegramId, $paymentId): PaymentResource|JsonResponse
    {
        // Находим клиента по telegram_id
        $client = Client::where('telegram_id', $telegramId)->first();

        if (!$client) {
            return response()->json([
                'message' => 'Клиент с указанным Telegram ID не найден.'
            ], 404);
        }

        // Находим платеж, принадлежащий этому клиенту
        $payment = Payment::with(['client', 'rental'])
            ->where('id', $paymentId)
            ->where('client_id', $client->user_id)
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Платеж не найден или не принадлежит указанному клиенту.'
            ], 404);
        }

        return new PaymentResource($payment);
    }
}
