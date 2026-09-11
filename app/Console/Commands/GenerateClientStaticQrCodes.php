<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Repositories\ClientRepository;
use Illuminate\Console\Command;

class GenerateClientStaticQrCodes extends Command
{
    protected $signature = 'clients:generate-static-qr
        {--force : Перегенерировать QR даже у клиентов, у которых он уже есть}
        {--chunk=50 : Сколько клиентов обрабатывать за один проход}
        {--sleep=300 : Пауза между запросами к банку в миллисекундах}
        {--limit= : Обработать не более N клиентов (для тестового запуска)}
        {--dry-run : Только посчитать, кому нужен QR, без обращения к банку}';

    protected $description = 'Формирует статические QR-коды оплаты для клиентов, у которых их ещё нет (бэкфилл существующей базы)';

    public function __construct(private readonly ClientRepository $clientRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $sleepMs = max(0, (int) $this->option('sleep'));
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        $query = Client::query()->with('customFields');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('qr_code_url')->orWhere('qr_code_url', '');
            });
        }

        $total = $limit !== null ? min($limit, $query->count()) : $query->count();

        if ($total === 0) {
            $this->info('Все клиенты уже имеют статический QR-код. Нечего делать.');
            return Command::SUCCESS;
        }

        $this->info("Клиентов к обработке: {$total}" . ($force ? ' (режим --force: перегенерируем всем)' : ''));

        if ($dryRun) {
            $this->info('Режим --dry-run: запросы к банку не выполняются.');
            return Command::SUCCESS;
        }

        $success = 0;
        $failed = 0;
        $failedIds = [];
        $processed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('user_id')->chunkById($chunkSize, function ($clients) use (&$success, &$failed, &$failedIds, &$processed, $sleepMs, $bar, $limit) {
            foreach ($clients as $client) {
                $ok = $this->clientRepository->generateStaticQrCode($client);

                if ($ok) {
                    $success++;
                } else {
                    $failed++;
                    $failedIds[] = $client->user_id;
                }

                $processed++;
                $bar->advance();

                if ($limit !== null && $processed >= $limit) {
                    return false;
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        }, 'user_id');

        $bar->finish();
        $this->newLine(2);

        $this->info("Готово. Успешно: {$success}. С ошибкой: {$failed}.");

        if ($failed > 0) {
            $this->warn('ID клиентов с ошибкой (подробности в storage/logs/laravel.log): ' . implode(', ', $failedIds));
            $this->line('Повторный запуск команды (без --force) обработает только тех, у кого до сих пор нет QR.');
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
