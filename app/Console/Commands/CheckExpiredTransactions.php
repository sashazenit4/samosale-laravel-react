<?php
// app/Console/Commands/CheckExpiredTransactions.php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class CheckExpiredTransactions extends Command
{
    protected $signature = 'transactions:check-expired';
    protected $description = 'Check and mark expired transactions';

    public function handle()
    {
        $expiredCount = Transaction::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$expiredCount} transactions as expired.");

        return Command::SUCCESS;
    }
}
