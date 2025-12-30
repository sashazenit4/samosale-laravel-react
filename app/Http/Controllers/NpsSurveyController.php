<?php

namespace App\Http\Controllers;

use App\Models\NpsSurvey;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NpsSurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $query = NpsSurvey::with(['client', 'rental']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтрация по клиенту
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Фильтрация по аренде
        if ($request->has('rental_id')) {
            $query->where('rental_id', $request->rental_id);
        }

        $surveys = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $surveys->items(),
            'pagination' => [
                'current_page' => $surveys->currentPage(),
                'per_page' => $surveys->perPage(),
                'total' => $surveys->total(),
                'last_page' => $surveys->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,user_id',
            'rental_id' => 'nullable|exists:rentals,id',
            'sent_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'score' => 'nullable|integer|min:0|max:10',
            'status' => 'sometimes|in:scheduled,sent,completed',
        ]);

        $survey = NpsSurvey::create($validated);
        $survey->load(['client', 'rental']);

        return response()->json([
            'success' => true,
            'message' => 'NPS опрос создан успешно',
            'data' => $survey
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(NpsSurvey $npsSurvey): JsonResponse
    {
        $npsSurvey->load(['client', 'rental']);

        return response()->json([
            'success' => true,
            'data' => $npsSurvey
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NpsSurvey $npsSurvey): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,user_id',
            'rental_id' => 'nullable|exists:rentals,id',
            'sent_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'score' => 'nullable|integer|min:0|max:10',
            'status' => 'sometimes|in:scheduled,sent,completed',
        ]);

        $npsSurvey->update($validated);
        $npsSurvey->load(['client', 'rental']);

        return response()->json([
            'success' => true,
            'message' => 'NPS опрос обновлён успешно',
            'data' => $npsSurvey
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NpsSurvey $npsSurvey): JsonResponse
    {
        $npsSurvey->delete();

        return response()->json([
            'success' => true,
            'message' => 'NPS опрос удалён успешно'
        ]);
    }

    public function getTodaySurveys(Request $request): JsonResponse
    {
        $surveys = NpsSurvey::with(['client', 'rental'])
            ->whereDate('sent_at', Carbon::today())
            ->get();

        return response()->json([
            'success' => true,
            'data' => $surveys,
        ]);
    }
}
