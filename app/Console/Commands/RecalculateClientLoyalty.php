<?php

namespace App\Console\Commands;

use App\Services\ClientLoyaltyService;
use Illuminate\Console\Command;

class RecalculateClientLoyalty extends Command
{
    protected $signature = 'clients:recalculate-loyalty {--no-notify : Не отправлять уведомления в Telegram}';

    protected $description = 'Пересчитывает total_spent и loyalty_level клиентов на основе transactions';

    public function __construct(private readonly ClientLoyaltyService $loyaltyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $notify = !$this->option('no-notify');

        $this->info('Recalculating clients loyalty data...');

        $result = $this->loyaltyService->recalculateAll($notify);

        $this->info("Done. Updated clients: {$result['updated_clients']}; Level changed: {$result['level_changed_clients']}");

        return Command::SUCCESS;
    }
}
