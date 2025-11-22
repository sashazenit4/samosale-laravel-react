<?php
// app/Http/Controllers/TransactionController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\TochkaBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Transaction::with(['payment', 'client']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтрация по типу
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Фильтрация по клиенту
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Фильтрация по платежу
        if ($request->has('payment_id')) {
            $query->where('payment_id', $request->payment_id);
        }

        // Фильтрация по дате создания
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Сортировка
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $transactions = $query->paginate($request->get('per_page', 15));

        return TransactionResource::collection($transactions);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($request->payment_id);

            if (!Transaction::canCreateForPayment($payment)) {
                return response()->json([
                    'message' => 'Невозможно создать транзакцию для этого платежа.'
                ], 422);
            }

            // Создаем транзакцию
            $transaction = Transaction::create([
                'payment_id' => $payment->id,
                'client_id' => $payment->client_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'type' => 'payment',
                'description' => $request->description ?? "Оплата платежа #{$payment->id}",
                'expires_at' => now()->addMinutes(15),
            ]);

            // Создаем QR-код через API банка
            $bankService = new TochkaBankService($request->get('environment', 'sandbox'));
            $qrCodeResult = $bankService->createQrCode($transaction);

            if (!isset($qrCodeResult['success'])) {
                DB::rollBack();
                Log::error('Invalid QR code result format', ['result' => $qrCodeResult]);
                return response()->json([
                    'message' => 'Неверный формат ответа от банка',
                    'error' => 'Invalid response format'
                ], 500);
            }

            if (!$qrCodeResult['success']) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Ошибка при создании QR-кода',
                    'error' => $qrCodeResult['error'] ?? 'Unknown error',
                    'code' => $qrCodeResult['code'] ?? 500
                ], 500);
            }

            // Обновляем транзакцию данными от банка
            $updateData = [
                'bank_response' => $qrCodeResult['response'] ?? null,
            ];

            if (isset($qrCodeResult['qr_code_id'])) {
                $updateData['qr_code_id'] = $qrCodeResult['qr_code_id'];
            }

            if (isset($qrCodeResult['qr_code_url'])) {
                $updateData['qr_code_url'] = $qrCodeResult['qr_code_url'];
            }

            // bank_transaction_id пока не устанавливаем - он придет при проверке статуса
            if (isset($qrCodeResult['image_data'])) {
                $imageData = $qrCodeResult['image_data'];
                if (isset($imageData['content'])) {
                    $updateData['image_data'] = $imageData['content'];
                }
                if (isset($imageData['mediaType'])) {
                    $updateData['image_media_type'] = $imageData['mediaType'];
                }
            }

            $transaction->update($updateData);

            DB::commit();

            return response()->json([
                'message' => 'Транзакция успешно создана',
                'data' => new TransactionResource($transaction->load(['payment', 'client']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Transaction creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Ошибка при создании транзакции',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load(['payment', 'client']));
    }

    /**
     * Update the specified transaction.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): TransactionResource
    {
        $transaction->update($request->validated());

        return new TransactionResource($transaction->load(['payment', 'client']));
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        // Можно удалять только определенные статусы
        if (!in_array($transaction->status, ['pending', 'cancelled', 'expired'])) {
            return response()->json([
                'message' => 'Невозможно удалить транзакцию с текущим статусом.'
            ], 422);
        }

        $transaction->delete();

        return response()->json([
            'message' => 'Транзакция успешно удалена.'
        ]);
    }

    /**
     * Проверка статуса транзакции через API банка
     * Обновленный метод согласно новой документации
     */
    public function checkStatus(Transaction $transaction): JsonResponse
    {
        try {
            // Проверяем, что у транзакции есть qr_code_id
            if (empty($transaction->qr_code_id)) {
                return response()->json([
                    'message' => 'У транзакции отсутствует идентификатор QR-кода'
                ], 422);
            }

            $bankService = new TochkaBankService();
            $statusResult = $bankService->checkPaymentStatus($transaction->qr_code_id);

            if ($statusResult['success']) {
                $oldStatus = $transaction->status;
                $newStatus = $statusResult['status'];

                $updateData = [
                    'status' => $newStatus,
                    'bank_response' => array_merge(
                        $transaction->bank_response ?? [],
                        ['status_check' => $statusResult['response']]
                    )
                ];

                // Обновляем bank_transaction_id если он пришел в ответе
                if (!empty($statusResult['bank_transaction_id'])) {
                    $updateData['bank_transaction_id'] = $statusResult['bank_transaction_id'];
                }

                // Если транзакция завершена, обновляем время оплаты
                if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                    $updateData['paid_at'] = now();
                }

                $transaction->update($updateData);

                // Если транзакция завершена, обновляем связанный платеж
                if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                    $this->processCompletedTransaction($transaction);
                }

                return response()->json([
                    'message' => 'Статус транзакции обновлен',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'bank_status' => $statusResult['bank_status'] ?? null,
                    'bank_status_message' => $statusResult['bank_status_message'] ?? null,
                    'bank_transaction_id' => $statusResult['bank_transaction_id'] ?? null,
                    'data' => new TransactionResource($transaction->load(['payment', 'client']))
                ]);
            } else {
                return response()->json([
                    'message' => 'Ошибка при проверке статуса',
                    'error' => $statusResult['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Transaction status check failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Ошибка при проверке статуса транзакции',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Массовая проверка статусов транзакций
     */
    public function checkMultipleStatus(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id',
        ]);

        try {
            $transactions = Transaction::whereIn('id', $request->transaction_ids)
                ->whereNotNull('qr_code_id')
                ->get();

            if ($transactions->isEmpty()) {
                return response()->json([
                    'message' => 'Не найдено транзакций с идентификаторами QR-кодов'
                ], 422);
            }

            $qrCodeIds = $transactions->pluck('qr_code_id')->toArray();

            $bankService = new TochkaBankService();
            $statusResult = $bankService->checkMultiplePaymentStatus($qrCodeIds);

            if (!$statusResult['success']) {
                return response()->json([
                    'message' => 'Ошибка при массовой проверке статусов',
                    'error' => $statusResult['error']
                ], 500);
            }

            $results = [];
            $updatedCount = 0;

            foreach ($transactions as $transaction) {
                $qrCodeId = $transaction->qr_code_id;

                if (isset($statusResult['results'][$qrCodeId])) {
                    $statusData = $statusResult['results'][$qrCodeId];
                    $oldStatus = $transaction->status;
                    $newStatus = $statusData['status'];

                    $updateData = [
                        'status' => $newStatus,
                        'bank_response' => array_merge(
                            $transaction->bank_response ?? [],
                            ['status_check' => $statusResult['response']]
                        )
                    ];

                    // Обновляем bank_transaction_id если он пришел в ответе
                    if (!empty($statusData['bank_transaction_id'])) {
                        $updateData['bank_transaction_id'] = $statusData['bank_transaction_id'];
                    }

                    // Если транзакция завершена, обновляем время оплаты
                    if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                        $updateData['paid_at'] = now();
                    }

                    $transaction->update($updateData);

                    // Если транзакция завершена, обновляем связанный платеж
                    if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                        $this->processCompletedTransaction($transaction);
                    }

                    $results[] = [
                        'transaction_id' => $transaction->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'bank_status' => $statusData['bank_status'] ?? null,
                        'bank_transaction_id' => $statusData['bank_transaction_id'] ?? null,
                    ];

                    $updatedCount++;
                }
            }

            return response()->json([
                'message' => "Статусы обновлены для {$updatedCount} транзакций",
                'updated_count' => $updatedCount,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Multiple transaction status check failed', [
                'transaction_ids' => $request->transaction_ids,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Ошибка при массовой проверке статусов',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обработка завершенной транзакции
     */
    private function processCompletedTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $payment = $transaction->payment;

            // Обновляем оплаченную сумму
            $newPaidAmount = $payment->paid_amount + $transaction->amount;
            $payment->update([
                'paid_amount' => $newPaidAmount,
                'paid_at' => now(),
            ]);

            Log::info('Payment updated after completed transaction', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'amount' => $transaction->amount,
                'new_paid_amount' => $newPaidAmount
            ]);
        });
    }

    /**
     * Отмена транзакции
     */
    public function cancel(Transaction $transaction): JsonResponse
    {
        if (!in_array($transaction->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Невозможно отменить транзакцию с текущим статусом.'
            ], 422);
        }

        $transaction->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Транзакция отменена',
            'data' => new TransactionResource($transaction->load(['payment', 'client']))
        ]);
    }

    /**
     * Получение транзакций по telegram_id клиента
     */
    public function byTelegramId(Request $request, $telegramId): AnonymousResourceCollection
    {
        $client = \App\Models\Client::where('telegram_id', $telegramId)->firstOrFail();

        $query = Transaction::with(['payment', 'client'])
            ->where('client_id', $client->user_id);

        // Фильтрация
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Сортировка
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $transactions = $query->paginate($request->get('per_page', 15));

        return TransactionResource::collection($transactions);
    }
}
