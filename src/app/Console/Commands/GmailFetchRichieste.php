<?php

namespace App\Console\Commands;

use App\Models\GmailAccount;
use App\Services\EmailExtractionService;
use App\Services\EmailIngestionService;
use App\Services\EmailThreadMatcherService;
use App\Services\GmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GmailFetchRichieste extends Command
{
    protected $signature = 'gmail:fetch-richieste
                            {--account= : Fetch only for a specific gmail_account ID}
                            {--company= : Fetch only for a specific company ID}';

    protected $description = 'Fetch new emails tagged with the configured label from all active Gmail accounts';

    public function handle(): int
    {
        $query = GmailAccount::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('ingestion_attiva', true)
            ->whereNotNull('label_richieste_id');

        if ($this->option('account')) {
            $query->where('id', $this->option('account'));
        }

        if ($this->option('company')) {
            $query->where('company_id', $this->option('company'));
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('Nessuna casella con ingestion attiva trovata.');
            return self::SUCCESS;
        }

        $totalProcessed = 0;

        foreach ($accounts as $account) {
            try {
                $processed = $this->fetchForAccount($account);
                $totalProcessed += $processed;
                $this->info("[{$account->email_address}] {$processed} email trovate.");
            } catch (\Exception $e) {
                $this->error("[{$account->email_address}] Errore: {$e->getMessage()}");
                Log::channel('gmail')->error("Gmail fetch error for {$account->email_address}", [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Totale: {$totalProcessed} email trovate.");
        return self::SUCCESS;
    }

    private function fetchForAccount(GmailAccount $account): int
    {
        $gmailService = new GmailService($account);

        // Initialize history_id if not set
        if (!$account->history_id) {
            $profile = $gmailService->getProfile();
            $account->update(['history_id' => $profile['historyId']]);
            $this->warn("[{$account->email_address}] Cursore inizializzato. Le nuove email saranno rilevate dal prossimo fetch.");
            return 0;
        }

        // Get known Gmail thread IDs for this mailbox to detect replies
        $knownThreadIds = \App\Models\ThreadEmail::withoutGlobalScopes()
            ->where('mailbox_id', $account->id)
            ->pluck('thread_id_gmail')
            ->unique()
            ->values()
            ->toArray();

        $result = $gmailService->fetchNewMessages(
            $account->history_id,
            $account->label_richieste_id,
            $knownThreadIds
        );

        // Update history_id
        $account->update(['history_id' => $result['history_id']]);

        $messageIds = $result['message_ids'];

        if (empty($messageIds)) {
            return 0;
        }

        // Process each message through the ingestion pipeline
        $ingestionService = new EmailIngestionService(
            new EmailThreadMatcherService(),
            new EmailExtractionService()
        );

        foreach ($messageIds as $messageId) {
            try {
                $detail = $gmailService->getMessageDetail($messageId);

                Log::channel('gmail')->info("New labeled email found", [
                    'account' => $account->email_address,
                    'message_id' => $messageId,
                    'from' => $detail['from'],
                    'subject' => $detail['subject'],
                    'message_id_rfc' => $detail['message_id_rfc'],
                ]);

                $result = $ingestionService->ingest($account, $detail);

                Log::channel('gmail')->info("Ingestion result", [
                    'account' => $account->email_address,
                    'message_id' => $messageId,
                    'result' => $result['status'],
                    'richiesta_id' => $result['richiesta_id'] ?? null,
                ]);
            } catch (\Exception $e) {
                Log::channel('gmail')->error("Error processing message {$messageId}", [
                    'account' => $account->email_address,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return count($messageIds);
    }
}
