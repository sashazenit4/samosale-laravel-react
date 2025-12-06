<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Экспорт данных из таблицы
     */
    public function exportTable(string $table, Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $filters = $request->all();

            $result = $this->exportService->exportToExcel($table, $filters);

            return response()->download(
                $result['path'],
                $result['name'],
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Если это API запрос (например, через Postman), возвращаем JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }

            // Для веб-запросов можно редирект или показать страницу с ошибкой
            abort(400, $e->getMessage());
        }
    }

    /**
     * API-экспорт данных из таблицы (только для Postman/API запросов)
     */
    public function apiExportTable(string $table, Request $request): JsonResponse
    {
        try {
            $filters = $request->all();

            $result = $this->exportService->exportToExcel($table, $filters);

            // Для API возвращаем информацию о файле, но не сам файл
            return response()->json([
                'success' => true,
                'message' => 'Данные успешно экспортированы',
                'file_name' => $result['name'],
                'row_count' => $result['count'],
                'download_url' => url('/export/download/' . basename($result['path']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Отдельный метод для скачивания файла по имени
     */
    public function downloadFile(string $filename): BinaryFileResponse
    {
        $filePath = storage_path('app/exports/' . $filename);

        if (!file_exists($filePath)) {
            abort(404, 'Файл не найден');
        }

        return response()->download(
            $filePath,
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    /**
     * Получить колонки таблицы для фильтрации
     */
    public function getTableColumns(string $table): JsonResponse
    {
        try {
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

            if (empty($columns)) {
                throw new \Exception("Table {$table} does not exist or has no columns");
            }

            // Format columns for display
            $formattedColumns = array_map(function($column) {
                return [
                    'name' => $column,
                    'display' => ucwords(str_replace('_', ' ', $column))
                ];
            }, $columns);

            return response()->json([
                'success' => true,
                'columns' => $formattedColumns
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Получить список доступных таблиц
     */
    public function getAvailableTables(): JsonResponse
    {
        try {
            $tables = $this->exportService->getAvailableTables();

            return response()->json([
                'success' => true,
                'tables' => $tables
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
