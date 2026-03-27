<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

class RefreshTransactionStatusMaps extends Command
{
    protected $signature = 'services:refresh-transaction-maps';
    protected $description = 'Refresh transaction_status_map for all services that have accounting transactions';

    public function handle(): int
    {
        $services = Service::whereHas('accountingTransactions')->get();
        $bar = $this->output->createProgressBar($services->count());

        foreach ($services as $service) {
            $service->refreshTransactionStatusMap();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Refreshed {$services->count()} services.");

        return Command::SUCCESS;
    }
}
