<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $equipment = Equipment::when($request->status, function($query, $status) {
            $query->where('status', $status);
        })
            ->when($request->number, function($query, $number) {
                $query->where('number', 'like', "%{$number}%");
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'required|string|max:255|unique:equipment',
            'status' => 'required|in:stolen,free,rented'
        ]);

        $equipment = Equipment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Оборудование создано успешно',
            'data' => $equipment
        ], 201);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'sometimes|string|max:255|unique:equipment,number,' . $equipment->id,
            'status' => 'sometimes|in:stolen,free,rented'
        ]);

        $equipment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Оборудование обновлено успешно',
            'data' => $equipment
        ]);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Оборудование удалено успешно'
        ]);
    }

    public function getByStatus(string $status): JsonResponse
    {
        if (!in_array($status, ['stolen', 'free', 'rented'])) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный статус'
            ], 400);
        }

        $equipment = Equipment::where('status', $status)->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }
}
