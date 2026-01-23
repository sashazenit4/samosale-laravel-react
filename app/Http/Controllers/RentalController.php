<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RentalResource;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Client;
use App\Models\Bike;
use App\Models\Tariff;
use App\Services\RentalPriceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RentalController extends Controller
{
    protected $rentalPriceService;

    public function __construct(RentalPriceService $rentalPriceService)
    {
        $this->rentalPriceService = $rentalPriceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $rentals = Rental::with(['client', 'bike', 'tariff'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => RentalResource::collection($rentals),
                'meta' => [
                    'current_page' => $rentals->currentPage(),
                    'last_page' => $rentals->lastPage(),
                    'per_page' => $rentals->perPage(),
                    'total' => $rentals->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rentals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,user_id',
            'bike_id' => 'required|exists:bikes,id',
            'tariff_id' => 'required|exists:tariffs,id',
            'battery_capacity' => 'required|string|max:255',
            'batteries_count' => 'required|integer|min:1|max:2',
            'start_date' => 'required|date',
            'planned_end_date' => 'required|date|after:start_date',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Проверяем, что байк свободен
            $bike = Bike::find($request->bike_id);
            if ($bike->status !== 'free') {
                return response()->json([
                    'success' => false,
                    'message' => 'Bike is not available for rental'
                ], 422);
            }

            // Получаем тариф
            $tariff = Tariff::find($request->tariff_id);

            // Рассчитываем стоимость
            $priceCalculation = $this->rentalPriceService->calculateRentalPrice(
                $tariff,
                Carbon::parse($request->start_date),
                Carbon::parse($request->planned_end_date)
            );

            // Создаем аренду
            $rental = Rental::create(array_merge($validator->validated(), [
                'total_cost' => $priceCalculation['total_price']
            ]));

            $this->createPaymentsForRental($rental);

            // Меняем статус байка
            $bike->status = 'renting';
            $bike->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rental created successfully',
                'data' => new RentalResource($rental->load(['client', 'bike', 'tariff']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create rental',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Rental $rental): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => new RentalResource($rental->load(['client', 'bike', 'tariff']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rental',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Перерасчет платежей при изменении даты окончания аренды
     */
    private function recalculateRentalPayments(Rental $rental, Carbon $oldEndDate): array
    {
        $tariff = $rental->tariff;
        $newEndDate = Carbon::parse($rental->planned_end_date);
        $purpose = 'Услуги проката';

        // Вычисляем разницу в днях между старой и новой датой окончания
        $extensionDays = $oldEndDate->diffInDays($newEndDate);

        // Если период не увеличился, не создаем новые платежи
        if ($extensionDays <= 0) {
            return $rental->payments->all();
        }

        // Рассчитываем стоимость дополнительного периода
        $extensionCalculation = $this->rentalPriceService->calculateRentalPrice(
            $tariff,
            $oldEndDate,
            $newEndDate,
            abs($oldEndDate->diffInDays($rental->start_date))
        );

        $newPayments = [];
        $currentDate = $oldEndDate->copy();

        // Создаем новые платежи только для дополнительного периода
        $paymentDate = $oldEndDate->copy();
        foreach ($extensionCalculation['breakdown'] as $period) {
            if ($period['amount'] > 0) {
                $payment = Payment::create([
                    'client_id' => $rental->client_id,
                    'total_amount' => $period['amount'],
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'payment_type' => 'cashless',
                    'article' => 'bike_rental',
                    'purpose' => "{$purpose} - {$period['description']} (продление)",
                    'rental_id' => $rental->id,
                    'year' => $currentDate->year,
                    'month' => strtolower($currentDate->englishMonth),
                    'generated_at' => $paymentDate,
                ]);
                if ('week' === $period['type']) {
                    $paymentDate->addWeek();
                } elseif ('month' === $period['type']) {
                    $paymentDate->addMonth();
                }

                $newPayments[] = $payment;

                // Обновляем дату для следующего платежа
                if ($period['type'] === 'month') {
                    $currentDate->addMonth();
                } elseif ($period['type'] === 'week') {
                    $currentDate->addWeek();
                } else {
                    $currentDate->addDays($period['days'] ?? 7);
                }
            }
        }

        // Обновляем общую стоимость аренды
        $rental->update([
            'total_cost' => $rental->total_cost + $extensionCalculation['total_price']
        ]);

        return array_merge($rental->payments->all(), $newPayments);
    }

    /**
     * Обновление метода update для правильной обработки изменений дат
     */
    public function update(Request $request, Rental $rental): JsonResponse
    {
        if (!$rental->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Only active rentals can be updated'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'bike_id' => 'sometimes|required|exists:bikes,id',
            'tariff_id' => 'sometimes|required|exists:tariffs,id',
            'battery_capacity' => 'sometimes|required|string|max:255',
            'batteries_count' => 'sometimes|required|integer|min:1|max:2',
            'planned_end_date' => 'sometimes|required|date|after:start_date',
            'actual_end_date' => 'sometimes|required|date|after:start_date',
            'total_cost' => 'sometimes|required|numeric|min:0',
            'note' => 'nullable|string',
            'status' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldBikeId = $rental->bike_id;
            $newBikeId = $request->bike_id ?? $rental->bike_id;
            $oldPlannedEndDate = Carbon::parse($rental->planned_end_date);
            $newPlannedEndDate = $request->planned_end_date
                ? Carbon::parse($request->planned_end_date)
                : $oldPlannedEndDate->copy();

            // Проверяем, изменилась ли дата окончания или тариф
            $dateChanged = $newPlannedEndDate->ne($oldPlannedEndDate);
            $tariffChanged = $request->has('tariff_id') && $request->tariff_id != $rental->tariff_id;

            // Сохраняем старую стоимость для возможного перерасчета
            $oldTotalCost = $rental->total_cost;

            // Обновляем аренду
            $rental->update($validator->validated());

            // Если изменился тариф, полностью пересчитываем стоимость
            if ($tariffChanged && $rental->isActive()) {
                $tariff = $rental->tariff;
                $startDate = Carbon::parse($rental->start_date);
                $endDate = Carbon::parse($rental->planned_end_date);

                $priceCalculation = $this->rentalPriceService->calculateRentalPrice(
                    $tariff,
                    $startDate,
                    $endDate,
                );

                // Обновляем общую стоимость
                $rental->update(['total_cost' => $priceCalculation['total_price']]);

            }

            // Если увеличилась дата окончания, добавляем платежи за дополнительный период
            if ($dateChanged && $newPlannedEndDate->gt($oldPlannedEndDate) && $rental->isActive()) {
                $this->recalculateRentalPayments($rental, $oldPlannedEndDate);
            }

            // Если уменьшилась дата окончания, просто обновляем общую стоимость
            // (не удаляем существующие платежи)
            if ($dateChanged && $newPlannedEndDate->lt($oldPlannedEndDate) && $rental->isActive()) {
                $tariff = $rental->tariff;
                $startDate = Carbon::parse($rental->start_date);
                $endDate = Carbon::parse($rental->planned_end_date);

                $priceCalculation = $this->rentalPriceService->calculateRentalPrice(
                    $tariff,
                    $startDate,
                    $endDate
                );

                // Обновляем общую стоимость
                $rental->update(['total_cost' => $priceCalculation['total_price']]);
            }

            // Если поменялся байк, обновляем статусы
            if ($oldBikeId != $newBikeId) {
                // Освобождаем старый байк
                $oldBike = Bike::find($oldBikeId);
                $oldBike->status = 'free';
                $oldBike->save();

                // Занимаем новый байк
                $newBike = Bike::find($newBikeId);
                $newBike->status = 'renting';
                $newBike->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rental updated successfully',
                'data' => new RentalResource($rental->load(['client', 'bike', 'tariff', 'payments']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update rental',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rental $rental): JsonResponse
    {
        if ($rental->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active rental. Complete it first.'
            ], 422);
        }

        try {
            $rental->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rental deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rental',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete rental (normal completion)
     */
    public function complete(Rental $rental): JsonResponse
    {
        if (!$rental->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Rental is already completed'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $rental->update([
                'status' => 'completed',
                'actual_end_date' => now()
            ]);

            // Освобождаем байк
            $bike = $rental->bike;
            $bike->status = 'free';
            $bike->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rental completed successfully',
                'data' => new RentalResource($rental->load(['client', 'bike', 'tariff']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete rental',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete rental early
     */
    public function completeEarly(Request $request, Rental $rental): JsonResponse
    {
        if (!$rental->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Rental is already completed'
            ], 422);
        }

        if (!$rental->canCompleteEarly()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot complete rental early'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'completion_type' => 'required|in:bike_change,cancellation',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $actualEndDate = now();

            // Рассчитываем сумму возврата
            $refundAmount = $this->rentalPriceService->calculateRefundAmount(
                $rental->total_cost,
                $rental->start_date,
                $rental->planned_end_date,
                $actualEndDate,
                $request->completion_type
            );

            $rental->update([
                'status' => 'completed_early',
                'actual_end_date' => $actualEndDate,
                'completion_type' => $request->completion_type,
                'refund_amount' => $refundAmount,
                'note' => $request->note ?? $rental->note
            ]);

            // Если есть сумма возврата, добавляем на баланс
            if ($refundAmount > 0) {
                $rental->client->addToBalance($refundAmount);
            }

            // Освобождаем байк
            $bike = $rental->bike;
            $bike->status = 'free';
            $bike->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rental completed early successfully',
                'data' => new RentalResource($rental->load(['client', 'bike', 'tariff']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete rental early',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel rental with bike change
     */
    public function cancelWithBikeChange(Request $request, Rental $rental): JsonResponse
    {
        if (!$rental->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Rental is already completed'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'new_bike_id' => 'required|exists:bikes,id',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $newBikeId = $request->new_bike_id;
            $oldBike = $rental->bike;
            $newBike = Bike::find($newBikeId);

            // Проверяем, что новый байк свободен
            if ($newBike->status !== 'free') {
                return response()->json([
                    'success' => false,
                    'message' => 'New bike is not available for rental'
                ], 422);
            }

            // Проверяем, что это не тот же байк
            if ($oldBike->id === $newBikeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'New bike must be different from current bike'
                ], 422);
            }

            $actualEndDate = now();

            // Завершаем изначальную аренду
            $rental->update([
                'status' => 'completed_early',
                'actual_end_date' => $actualEndDate,
                'completion_type' => 'bike_change',
                'refund_amount' => 0,
                'note' => $request->note ?? $rental->note
            ]);

            // Освобождаем изначальный байк
            $oldBike->status = 'free';
            $oldBike->save();

            // Создаем копию аренды с новым байком
            $newRental = Rental::create([
                'client_id' => $rental->client_id,
                'bike_id' => $newBikeId,
                'tariff_id' => $rental->tariff_id,
                'battery_capacity' => $rental->battery_capacity,
                'batteries_count' => $rental->batteries_count,
                'start_date' => now(),
                'planned_end_date' => $rental->planned_end_date,
                'total_cost' => $rental->total_cost,
                'paid_amount' => $rental->paid_amount,
                'paid_status' => $rental->paid_status,
                'status' => 'active',
                'note' => $rental->note
            ]);

            // Перепривязываем платежи к новой аренде
            $rental->payments()->update(['rental_id' => $newRental->id]);

            // Занимаем новый байк
            $newBike->status = 'renting';
            $newBike->save();

            // Обновляем статус платежей для новой аренды
            $newRental->updatePaymentStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rental cancelled with bike change successfully',
                'data' => [
                    'old_rental' => new RentalResource($rental->load(['client', 'bike', 'tariff'])),
                    'new_rental' => new RentalResource($newRental->load(['client', 'bike', 'tariff', 'payments']))
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel rental with bike change',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark rental as paid
     */
    public function markAsPaid(Rental $rental): JsonResponse
    {
        try {
            DB::beginTransaction();

            $rental->update([
                'paid_status' => 'paid',
                'paid_amount' => $rental->total_cost
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rental marked as paid',
                'data' => new RentalResource($rental->load(['client', 'bike', 'tariff']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark rental as paid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate rental price
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tariff_id' => 'required|exists:tariffs,id',
            'start_date' => 'required|date',
            'planned_end_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tariff = Tariff::find($request->tariff_id);

            $calculation = $this->rentalPriceService->calculateRentalPrice(
                $tariff,
                Carbon::parse($request->start_date),
                Carbon::parse($request->planned_end_date)
            );

            return response()->json([
                'success' => true,
                'data' => $calculation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate price',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создание платежей для аренды на основе тарифа и длительности
     */
    private function createPaymentsForRental(Rental $rental): array
    {
        $tariff = $rental->tariff;
        $startDate = Carbon::parse($rental->start_date);
        $endDate = Carbon::parse($rental->planned_end_date);
        $purpose = 'Услуги проката';

        // Получаем детальный расчет стоимости из сервиса
        $priceCalculation = $this->rentalPriceService->calculateRentalPrice(
            $tariff,
            $startDate,
            $endDate
        );

        $payments = [];
        $currentDate = $startDate->copy();

        // Создаем платежи на основе breakdown
        $paymentDate = $currentDate;
        foreach ($priceCalculation['breakdown'] as $period) {
            if ($period['amount'] > 0) {
                $payment = Payment::create([
                    'client_id' => $rental->client_id,
                    'total_amount' => $period['amount'],
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'payment_type' => 'cashless',
                    'article' => 'bike_rental',
                    'purpose' => "{$purpose} - {$period['description']}",
                    'rental_id' => $rental->id,
                    'year' => $currentDate->year,
                    'month' => strtolower($currentDate->englishMonth),
                    'generated_at' => $paymentDate,
                ]);
                $paymentDate->addWeek();

                $payments[] = $payment;

                // Обновляем дату для следующего платежа в зависимости от типа периода
                if ($period['type'] === 'month') {
                    $currentDate->addMonth();
                } elseif ($period['type'] === 'week') {
                    $currentDate->addWeek();
                } else {
                    $currentDate->addDays($period['days'] ?? 7);
                }
            }
        }

        // Проверяем и корректируем разницу в суммах
        $this->adjustPaymentAmounts($rental, $payments);

        return $payments;
    }

    /**
     * Корректировка сумм платежей для соответствия общей стоимости
     */
    private function adjustPaymentAmounts(Rental $rental, array &$payments): void
    {
        if (empty($payments)) {
            return;
        }

        $totalPaymentsAmount = array_reduce($payments, function ($sum, $payment) {
            return $sum + $payment->total_amount;
        }, 0);

        $difference = $rental->total_cost - $totalPaymentsAmount;

        if (abs($difference) > 0.01) {
            $lastPayment = end($payments);
            $lastPayment->update([
                'total_amount' => $lastPayment->total_amount + $difference
            ]);

            // Обновляем объект в массиве
            $payments[count($payments) - 1] = $lastPayment->fresh();
        }
    }
}
