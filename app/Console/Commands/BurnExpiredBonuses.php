<?php

namespace App\Console\Commands;

use App\Models\BonusOperation;
use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BurnExpiredBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonuses:burn-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Burn (expire) bonuses that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to burn expired bonuses...');

        try {
            DB::beginTransaction();

            // Get all expired bonus operations that haven't been fully used
            $expiredBonuses = BonusOperation::accruals()
                ->expired()
                ->whereRaw('used_amount < amount')
                ->with('client')
                ->get();

            if ($expiredBonuses->isEmpty()) {
                $this->info('No expired bonuses to burn.');
                DB::commit();
                return Command::SUCCESS;
            }

            $this->info("Found {$expiredBonuses->count()} expired bonus operations to process.");

            $totalBurned = 0;
            $clientsAffected = [];

            // Group by client to process efficiently
            $bonusesByClient = $expiredBonuses->groupBy('client_id');

            foreach ($bonusesByClient as $clientId => $clientBonuses) {
                $client = Client::find($clientId);

                if (!$client) {
                    $this->warn("Client with ID {$clientId} not found, skipping bonuses.");
                    continue;
                }

                $clientBurnedAmount = 0;

                foreach ($clientBonuses as $bonus) {
                    $availableAmount = $bonus->amount;

                    if ($availableAmount > 0) {
                        // Mark the remaining amount as used (burned)
                        $bonus->used_amount = $bonus->amount;
                        $bonus->save();

                        // Deduct from client's bonus balance
                        $clientBurnedAmount += $availableAmount;

                        // Create a deduction record for tracking
                        BonusOperation::create([
                            'client_id' => $client->user_id,
                            'amount' => $availableAmount,
                            'type' => 'deduction',
                            'description' => "Сгорание бонусов (истек срок действия)",
                            'metadata' => [
                                'operation_type' => 'expiration',
                                'expired_bonus_id' => $bonus->id,
                                'expired_at' => $bonus->expires_at->toDateTimeString(),
                            ],
                        ]);

                        $this->line("  - Burned {$availableAmount} from bonus operation #{$bonus->id} for client #{$clientId}");
                    }
                }

                if ($clientBurnedAmount > 0) {
                    // Deduct the total burned amount from client's bonus balance
                    $client->bonus_balance = max(0, $client->bonus_balance - $clientBurnedAmount);
                    $client->save();

                    $clientsAffected[$clientId] = $clientBurnedAmount;
                    $totalBurned += $clientBurnedAmount;

                    $this->info("  Client #{$clientId}: burned {$clientBurnedAmount} bonuses");
                }
            }

            DB::commit();

            $this->info("Successfully burned {$totalBurned} bonuses for " . count($clientsAffected) . " clients.");

            Log::info('Expired bonuses burned', [
                'total_burned' => $totalBurned,
                'clients_affected' => count($clientsAffected),
                'details' => $clientsAffected,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("Error burning expired bonuses: " . $e->getMessage());
            Log::error('Failed to burn expired bonuses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
