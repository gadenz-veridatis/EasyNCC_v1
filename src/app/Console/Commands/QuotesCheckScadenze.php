<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\QuoteStateMachineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QuotesCheckScadenze extends Command
{
    protected $signature = 'quotes:check-scadenze';

    protected $description = 'Transition sent quotes past their expiry date to scaduto status';

    public function handle(): int
    {
        $quotes = Quote::withoutGlobalScopes()
            ->where('status', Quote::STATUS_SENT)
            ->whereNotNull('scadenza')
            ->where('scadenza', '<', now()->toDateString())
            ->get();

        if ($quotes->isEmpty()) {
            $this->info('Nessun preventivo scaduto trovato.');
            return self::SUCCESS;
        }

        $stateMachine = new QuoteStateMachineService();
        $count = 0;

        foreach ($quotes as $quote) {
            try {
                $stateMachine->transitionToScaduto($quote);
                $count++;
                $this->info("Quote #{$quote->id} → scaduto (scadenza: {$quote->scadenza->toDateString()})");
            } catch (\Exception $e) {
                $this->error("Quote #{$quote->id}: {$e->getMessage()}");
                Log::error("Failed to expire quote {$quote->id}: " . $e->getMessage());
            }
        }

        $this->info("{$count} preventivi transizionati a scaduto.");
        return self::SUCCESS;
    }
}
