<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;

class TransactionExportController extends Controller
{
    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * API: Получить доступные фильтры для транзакций
     */
    public function getFilters(): JsonResponse
    {
        $filters = [
            'status' => [
                'name' => 'status',
                'label' => 'Статус',
                'type' => 'select',
                'options' => [
                    ['value' => 'pending', 'label' => 'Ожидание'],
                    ['value' => 'processing', 'label' => 'В обработке'],
                    ['value' => 'completed', 'label' => 'Завершено'],
                    ['value' => 'failed', 'label' => 'Ошибка'],
                    ['value' => 'expired', 'label' => 'Просрочено'],
                    ['value' => 'cancelled', 'label' => 'Отменено'],
                    ['value' => '', 'label' => 'Все статусы']
                ]
            ],
            'type' => [
                'name' => 'type',
                'label' => 'Тип',
                'type' => 'select',
                'options' => [
                    ['value' => 'payment', 'label' => 'Платеж'],
                    ['value' => 'refund', 'label' => 'Возврат'],
                    ['value' => '', 'label' => 'Все типы']
                ]
            ],
            'client' => [
                'name' => 'client',
                'label' => 'Клиент (имя или телефон)',
                'type' => 'text',
                'placeholder' => 'Введите имя или номер телефона'
            ],
            'date_from' => [
                'name' => 'date_from',
                'label' => 'Дата создания от',
                'type' => 'date'
            ],
            'date_to' => [
                'name' => 'date_to',
                'label' => 'Дата создания до',
                'type' => 'date'
            ],
            'paid_date_from' => [
                'name' => 'paid_date_from',
                'label' => 'Дата оплаты от',
                'type' => 'date'
            ],
            'paid_date_to' => [
                'name' => 'paid_date_to',
                'label' => 'Дата оплаты до',
                'type' => 'date'
            ],
            'amount_min' => [
                'name' => 'amount_min',
                'label' => 'Сумма от',
                'type' => 'number',
                'step' => '0.01',
                'min' => '0'
            ],
            'amount_max' => [
                'name' => 'amount_max',
                'label' => 'Сумма до',
                'type' => 'number',
                'step' => '0.01',
                'min' => '0'
            ],
            'bank_transaction_id' => [
                'name' => 'bank_transaction_id',
                'label' => 'ID банковской транзакции',
                'type' => 'text',
                'placeholder' => 'Введите ID банковской транзакции'
            ],
            'order_by' => [
                'name' => 'order_by',
                'label' => 'Сортировка по',
                'type' => 'select',
                'options' => [
                    ['value' => 'transactions.created_at', 'label' => 'Дате создания'],
                    ['value' => 'transactions.paid_at', 'label' => 'Дате оплаты'],
                    ['value' => 'transactions.amount', 'label' => 'Сумме'],
                    ['value' => 'clients.name', 'label' => 'Имени клиента']
                ]
            ],
            'order_dir' => [
                'name' => 'order_dir',
                'label' => 'Направление сортировки',
                'type' => 'select',
                'options' => [
                    ['value' => 'desc', 'label' => 'По убыванию'],
                    ['value' => 'asc', 'label' => 'По возрастанию']
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'filters' => $filters,
            'default_values' => [
                'order_by' => 'transactions.created_at',
                'order_dir' => 'desc'
            ]
        ]);
    }

    /**
     * API: Экспорт транзакций с фильтрацией
     */
    public function exportTransactions(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();

            // Очищаем пустые фильтры
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->exportService->exportTransactionsWithFilters($filters);

            return response()->json([
                'success' => true,
                'message' => 'Транзакции успешно экспортированы',
                'data' => [
                    'file_name' => $result['name'],
                    'row_count' => $result['count'],
                    'total_amount' => $result['total_amount'],
                    'total_bonus_deduct' => $result['total_bonus_deduct'],
                    'download_url' => url('/api/transactions/export/download/' . basename($result['path'])),
                    'direct_download_url' => url('/api/transactions/export/direct?' . http_build_query($filters))
                ],
                'summary' => [
                    'total_records' => $result['count'],
                    'total_amount' => number_format($result['total_amount'], 2, '.', ' '),
                    'total_bonus_deduct' => number_format($result['total_bonus_deduct'], 2, '.', ' '),
                    'net_amount' => number_format($result['total_amount'] - $result['total_bonus_deduct'], 2, '.', ' ')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 400);
        }
    }

    /**
     * Прямое скачивание файла транзакций
     */
    public function directExport(Request $request): BinaryFileResponse
    {
        try {
            $filters = $request->all();

            // Очищаем пустые фильтры
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->exportService->exportTransactionsWithFilters($filters);

            return response()->download(
                $result['path'],
                $result['name'],
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Создаем файл с ошибкой
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Ошибка экспорта транзакций');
            $sheet->setCellValue('A2', $e->getMessage());

            $fileName = 'transactions_error_' . date('Y-m-d_His') . '.xlsx';
            $filePath = storage_path('app/exports/' . $fileName);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);

            return response()->download(
                $filePath,
                'error_export.xlsx'
            )->deleteFileAfterSend(true);
        }
    }

    /**
     * Скачивание файла по имени
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
     * Веб-форма для экспорта транзакций
     */
    public function showForm()
    {
        return view('transactions.export-form');
    }

    /**
     * Получить статистику по транзакциям
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $query = \Illuminate\Support\Facades\DB::table('transactions')
                ->join('clients', 'transactions.client_id', '=', 'clients.user_id');

            // Применяем фильтры, если есть
            $filters = $request->all();
            if (!empty($filters)) {
                $exportService = new ExportService();
                $exportService->applyTransactionFilters($query, $filters);
            }

            // Общая статистика
            $total = $query->count();
            $totalAmount = $query->sum('transactions.amount');
            $totalBonus = $query->sum('transactions.bonus_deduct_amount');

            // Статистика по статусам
            $statusStats = \Illuminate\Support\Facades\DB::table('transactions')
                ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->status => $item->count];
                });

            // Статистика по типам
            $typeStats = \Illuminate\Support\Facades\DB::table('transactions')
                ->select('type', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->type => $item->count];
                });

            // Последние 5 транзакций
            $recentTransactions = \Illuminate\Support\Facades\DB::table('transactions')
                ->join('clients', 'transactions.client_id', '=', 'clients.user_id')
                ->select('transactions.id', 'transactions.amount', 'transactions.status', 'clients.name', 'transactions.created_at')
                ->orderBy('transactions.created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_transactions' => $total,
                    'total_amount' => $totalAmount,
                    'total_bonus_deducted' => $totalBonus,
                    'net_amount' => $totalAmount - $totalBonus,
                    'status_distribution' => $statusStats,
                    'type_distribution' => $typeStats,
                    'average_transaction' => $total > 0 ? $totalAmount / $total : 0
                ],
                'recent_transactions' => $recentTransactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
