<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GmailAccount;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GmailAccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getCompanyId($request);

        $accounts = GmailAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('account_label')
            ->get()
            ->map(fn($a) => $this->formatAccount($a));

        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_label' => 'required|string|max:255',
            'email_address' => 'required|email|max:255',
            'client_id' => 'required|string|max:500',
            'client_secret' => 'required|string|max:500',
            'refresh_token' => 'required|string|max:2000',
            'access_token' => 'nullable|string|max:2000',
            'token_expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $companyId = $this->getCompanyId($request);

        $account = GmailAccount::create(array_merge($validated, [
            'company_id' => $companyId,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Account Gmail creato con successo',
            'data' => $this->formatAccount($account),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $validated = $request->validate([
            'account_label' => 'sometimes|required|string|max:255',
            'email_address' => 'sometimes|required|email|max:255',
            'client_id' => 'sometimes|required|string|max:500',
            'client_secret' => 'sometimes|required|string|max:500',
            'refresh_token' => 'sometimes|required|string|max:2000',
            'access_token' => 'nullable|string|max:2000',
            'token_expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'label_richieste' => 'nullable|string|max:255',
            'subject_tag' => 'nullable|string|max:100',
            'ingestion_attiva' => 'boolean',
        ]);

        // Validate: ingestion can only be activated if label_richieste_id is set
        if (($validated['ingestion_attiva'] ?? false) && !$account->label_richieste_id) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile attivare l\'ingestion senza aver verificato la label. Usa il pulsante "Verifica label" prima.',
            ], 422);
        }

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Account Gmail aggiornato con successo',
            'data' => $this->formatAccount($account->fresh()),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account Gmail eliminato con successo',
        ]);
    }

    public function testConnection(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        try {
            $gmailService = new GmailService($account);
            $gmailService->testConnection();

            return response()->json([
                'success' => true,
                'message' => 'Connessione riuscita! Token aggiornato.',
                'data' => $this->formatAccount($account->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connessione fallita: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolve a Gmail label name to its internal ID for ingestion.
     */
    public function resolveLabel(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $validated = $request->validate([
            'label_name' => 'required|string|max:255',
        ]);

        try {
            $gmailService = new GmailService($account);
            $labelId = $gmailService->resolveLabelId($validated['label_name']);

            if (!$labelId) {
                return response()->json([
                    'success' => false,
                    'message' => "Label '{$validated['label_name']}' non trovata in questa casella Gmail. Verifica che la label esista.",
                ], 422);
            }

            $account->update([
                'label_richieste' => $validated['label_name'],
                'label_richieste_id' => $labelId,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Label verificata: '{$validated['label_name']}' → {$labelId}",
                'data' => $this->formatAccount($account->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella verifica della label: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Trigger manual email fetch for all active ingestion accounts.
     */
    public function fetchAll(Request $request)
    {
        $companyId = $this->getCompanyId($request);

        $accounts = GmailAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('ingestion_attiva', true)
            ->whereNotNull('label_richieste_id')
            ->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nessuna casella con ingestion attiva.',
                'total_processed' => 0,
            ]);
        }

        $ingestionService = new \App\Services\EmailIngestionService(
            new \App\Services\EmailThreadMatcherService(),
            new \App\Services\EmailExtractionService()
        );

        $totalProcessed = 0;
        $errors = [];

        foreach ($accounts as $account) {
            try {
                $gmailService = new GmailService($account);

                if (!$account->history_id) {
                    $profile = $gmailService->getProfile();
                    $account->update(['history_id' => $profile['historyId']]);
                    continue;
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

                $account->update(['history_id' => $result['history_id']]);

                foreach ($result['message_ids'] as $messageId) {
                    try {
                        $detail = $gmailService->getMessageDetail($messageId);
                        $ingestionService->ingest($account, $detail);
                        $totalProcessed++;
                    } catch (\Exception $e) {
                        $errors[] = "[{$account->email_address}] {$e->getMessage()}";
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "[{$account->email_address}] {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'message' => $totalProcessed > 0
                ? "{$totalProcessed} email processate."
                : 'Nessuna nuova email trovata.',
            'total_processed' => $totalProcessed,
            'errors' => $errors,
        ]);
    }

    /**
     * Trigger manual email fetch for a specific account.
     */
    public function fetchNow(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$account->ingestion_attiva) {
            return response()->json([
                'success' => false,
                'message' => 'Ingestion non attiva per questa casella.',
            ], 422);
        }

        if (!$account->label_richieste_id) {
            return response()->json([
                'success' => false,
                'message' => 'Label non configurata. Verifica la label prima di attivare il fetch.',
            ], 422);
        }

        try {
            $gmailService = new GmailService($account);

            // If no history_id yet, get current profile to initialize
            if (!$account->history_id) {
                $profile = $gmailService->getProfile();
                $account->update(['history_id' => $profile['historyId']]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cursore inizializzato. Il prossimo fetch rileverà le nuove email etichettate.',
                    'data' => $this->formatAccount($account->fresh()),
                    'processed' => 0,
                ]);
            }

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

            // Dispatch ingestion for each message (will be implemented in Step 4)
            // For now, just return the count
            return response()->json([
                'success' => true,
                'message' => count($messageIds) . ' email trovate con la label configurata.',
                'data' => $this->formatAccount($account->fresh()),
                'processed' => count($messageIds),
                'message_ids' => $messageIds,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel fetch: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function formatAccount(GmailAccount $account): array
    {
        return [
            'id' => $account->id,
            'company_id' => $account->company_id,
            'account_label' => $account->account_label,
            'email_address' => $account->email_address,
            'client_id' => $account->getRawOriginal('client_id'),
            'client_secret' => $account->getRawOriginal('client_secret'),
            'refresh_token' => $account->getRawOriginal('refresh_token'),
            'access_token' => $account->getRawOriginal('access_token'),
            'token_expires_at' => $account->token_expires_at,
            'is_active' => $account->is_active,
            'label_richieste' => $account->label_richieste,
            'label_richieste_id' => $account->label_richieste_id,
            'subject_tag' => $account->subject_tag,
            'history_id' => $account->history_id,
            'ingestion_attiva' => $account->ingestion_attiva,
            'created_at' => $account->created_at,
            'updated_at' => $account->updated_at,
        ];
    }

    private function getCompanyId(Request $request): int
    {
        $user = Auth::user();
        if ($user->role === 'super-admin' && $request->has('company_id')) {
            return (int) $request->company_id;
        }
        return $user->company_id;
    }
}
