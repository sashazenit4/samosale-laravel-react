<?php
namespace App\Http\Controllers;

use App\Models\Bike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BikeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bikes = Bike::when($request->status, function($query, $status) {
            $query->where('status', $status);
        })
            ->when($request->type, function($query, $type) {
                $query->where('type', 'like', "%{$type}%");
            })
            ->when($request->bike_number, function($query, $bikeNumber) {
                $query->where('bike_number', 'like', "%{$bikeNumber}%");
            })
            // Фильтрация по property полям (пример для property_1)
            ->when($request->property_1, function($query, $property) {
                $query->where('property_1', 'like', "%{$property}%");
            })
            ->when($request->property_2, function($query, $property) {
                $query->where('property_2', 'like', "%{$property}%");
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bikes
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bike_number' => 'required|string|max:255|unique:bikes',
            'frame_number' => 'required|string|max:255|unique:bikes',
            'status' => 'required|in:renting,free,stolen',
            'type' => 'required|string|max:255',
            'property_1' => 'nullable|string|max:255',
            'property_2' => 'nullable|string|max:255',
            'property_3' => 'nullable|string|max:255',
            'property_4' => 'nullable|string|max:255',
            'property_5' => 'nullable|string|max:255',
            'property_6' => 'nullable|string|max:255',
            'property_7' => 'nullable|string|max:255',
            'property_8' => 'nullable|string|max:255',
            'property_9' => 'nullable|string|max:255',
            'property_10' => 'nullable|string|max:255',
        ]);

        $bike = Bike::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bike created successfully',
            'data' => $bike
        ], 201);
    }

    public function show(Bike $bike): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $bike
        ]);
    }

    public function update(Request $request, Bike $bike): JsonResponse
    {
        $validated = $request->validate([
            'bike_number' => 'sometimes|string|max:255|unique:bikes,bike_number,' . $bike->id,
            'frame_number' => 'sometimes|string|max:255|unique:bikes,frame_number,' . $bike->id,
            'status' => 'sometimes|in:renting,free,stolen',
            'type' => 'sometimes|string|max:255',
            'property_1' => 'nullable|string|max:255',
            'property_2' => 'nullable|string|max:255',
            'property_3' => 'nullable|string|max:255',
            'property_4' => 'nullable|string|max:255',
            'property_5' => 'nullable|string|max:255',
            'property_6' => 'nullable|string|max:255',
            'property_7' => 'nullable|string|max:255',
            'property_8' => 'nullable|string|max:255',
            'property_9' => 'nullable|string|max:255',
            'property_10' => 'nullable|string|max:255',
        ]);

        $bike->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bike updated successfully',
            'data' => $bike
        ]);
    }

    public function destroy(Bike $bike): JsonResponse
    {
        $bike->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bike deleted successfully'
        ]);
    }

    public function getByStatus(string $status): JsonResponse
    {
        if (!in_array($status, ['renting', 'free', 'stolen'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 400);
        }

        $bikes = Bike::where('status', $status)->get();

        return response()->json([
            'success' => true,
            'data' => $bikes
        ]);
    }
}
