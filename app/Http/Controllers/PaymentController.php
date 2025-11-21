<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
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
}
