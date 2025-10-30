<?php
namespace App\Http\Controllers;

use App\Http\Requests\TariffRequest;
use App\Models\Tariff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    /**
     * Get all tariffs
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tariff::query();

        // Фильтрация по программе
        if ($request->has('program')) {
            $query->where('program', $request->program);
        }

        // Фильтрация по мощности
        if ($request->has('power')) {
            $query->where('power', $request->power);
        }

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $tariffs = $query->orderBy('program')->orderBy('power')->get();

        return response()->json([
            'success' => true,
            'data' => $tariffs,
            'meta' => [
                'total' => $tariffs->count(),
            ]
        ]);
    }

    /**
     * Get specific tariff
     */
    public function show(Tariff $tariff): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $tariff
        ]);
    }

    /**
     * Create new tariff
     */
    public function store(TariffRequest $request): JsonResponse
    {
        try {
            $tariff = Tariff::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Тариф успешно создан',
                'data' => $tariff
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании тарифа',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update tariff
     */
    public function update(TariffRequest $request, Tariff $tariff): JsonResponse
    {
        try {
            $tariff->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Тариф успешно обновлен',
                'data' => $tariff
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении тарифа',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete tariff
     */
    public function destroy(Tariff $tariff): JsonResponse
    {
        try {
            $tariff->delete();

            return response()->json([
                'success' => true,
                'message' => 'Тариф успешно удален'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении тарифа',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tariff by program and power
     */
    public function getByPower(int $power): JsonResponse
    {
        $tariff = Tariff::where('power', $power)
            ->active()
            ->first();

        if (!$tariff) {
            return response()->json([
                'success' => false,
                'message' => 'Тариф не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tariff
        ]);
    }
}
