<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RentalResource;
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
     * Update the specified resource in storage.
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

            // Обновляем аренду
            $rental->update($validator->validated());

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
                'data' => new RentalResource($rental->load(['client', 'bike', 'tariff']))
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
}
