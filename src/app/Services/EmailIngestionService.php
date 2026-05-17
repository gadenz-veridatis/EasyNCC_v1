<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\GmailAccount;
use App\Models\Richiesta;
use App\Models\RigaEstratta;
use App\Models\RigaRichiesta;
use App\Models\ThreadEmail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailIngestionService
{
    private EmailThreadMatcherService $threadMatcher;
    private EmailExtractionService $extractionService;

    public function __construct(
        EmailThreadMatcherService $threadMatcher,
        EmailExtractionService $extractionService
    ) {
        $this->threadMatcher = $threadMatcher;
        $this->extractionService = $extractionService;
    }

    /**
     * Ingest a single email message from a Gmail account.
     *
     * @param GmailAccount $account The Gmail account that received the message
     * @param array $messageDetail Output from GmailService::getMessageDetail()
     * @return array Result of the ingestion process
     */
    public function ingest(GmailAccount $account, array $messageDetail): array
    {
        $companyId = $account->company_id;
        $messageIdRfc = $messageDetail['message_id_rfc'];

        // Skip if already processed
        if ($messageIdRfc && ThreadEmail::withoutGlobalScopes()->where('message_id_rfc', $messageIdRfc)->exists()) {
            Log::channel('gmail')->info("Message already processed: {$messageIdRfc}");
            return ['status' => 'skipped', 'reason' => 'already_processed'];
        }

        // Skip messages sent FROM this mailbox (outbound sent directly via Gmail, not through the app)
        $senderEmail = $this->extractEmailAddress($messageDetail['from'] ?? '');
        if (strcasecmp($senderEmail, $account->email_address) === 0) {
            Log::channel('gmail')->info("Skipping outbound message from own mailbox: {$messageIdRfc}");
            return ['status' => 'skipped', 'reason' => 'own_outbound'];
        }

        // 1. Try to match to existing richiesta via thread
        $richiestaId = $this->threadMatcher->match(
            $companyId,
            $account->id,
            $messageDetail['thread_id'],
            $messageDetail['in_reply_to'],
            $messageDetail['references']
        );

        $isNewRichiesta = false;

        if (!$richiestaId) {
            // 2. New thread — find or create contact, then create richiesta
            $senderEmail = $this->extractEmailAddress($messageDetail['from']);
            $senderName = $this->extractName($messageDetail['from']);

            $contact = $this->findOrCreateContact($companyId, $senderEmail, $senderName);

            $richiesta = Richiesta::create([
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'contact_id' => $contact->id,
                'fonte' => Richiesta::FONTE_EMAIL,
                'stato' => Richiesta::STATO_NUOVA,
                'data_ricezione' => $messageDetail['internal_date'] ?? now(),
            ]);

            $richiestaId = $richiesta->id;
            $isNewRichiesta = true;

            Log::channel('gmail')->info("New richiesta created: {$richiestaId}", [
                'contact' => $senderEmail,
                'subject' => $messageDetail['subject'],
            ]);
        }

        // Update last inbound timestamp for unread tracking
        $richiestaObj = Richiesta::withoutGlobalScopes()->find($richiestaId);
        if ($richiestaObj) {
            $richiestaObj->update(['ultimo_messaggio_inbound_at' => now()]);
        }

        // 3. Save the email message
        $threadEmail = ThreadEmail::create([
            'id' => Str::uuid(),
            'company_id' => $companyId,
            'richiesta_id' => $richiestaId,
            'mailbox_id' => $account->id,
            'thread_id_gmail' => $messageDetail['thread_id'],
            'message_id_rfc' => $messageIdRfc,
            'in_reply_to_rfc' => $messageDetail['in_reply_to'],
            'references_rfc' => $messageDetail['references'],
            'direzione' => 'inbound',
            'mittente' => $messageDetail['from'] ?? '',
            'destinatario' => $messageDetail['to'] ?? '',
            'subject' => $messageDetail['subject'],
            'body_text' => $messageDetail['body_text'],
            'body_html' => $messageDetail['body_html'],
            'ricevuto_at' => $messageDetail['internal_date'] ?? now(),
            'created_at' => now(),
        ]);

        // 4. Extract service lines from every inbound message (saved to righe_estratte)
        // Skip extraction for confirmed/cancelled richieste
        $extraction = null;
        $richiestaObj = Richiesta::withoutGlobalScopes()->find($richiestaId);
        $shouldExtract = $richiestaObj && !in_array($richiestaObj->stato, [
            Richiesta::STATO_CONFERMATA,
            Richiesta::STATO_ANNULLATA,
        ]);

        if ($shouldExtract) {
            $bodyForExtraction = $messageDetail['body_text'] ?? strip_tags($messageDetail['body_html'] ?? '');

            if (!empty($bodyForExtraction)) {
                $extraction = $this->extractionService->extract($bodyForExtraction, $messageDetail['subject']);

                if (!empty($extraction['righe'])) {
                    foreach ($extraction['righe'] as $index => $riga) {
                        $hasData = !empty($riga['data_servizio']) || !empty($riga['pickup'])
                            || !empty($riga['dropoff']) || !empty($riga['passeggeri'])
                            || !empty($riga['note']);
                        if (!$hasData) {
                            continue;
                        }

                        RigaEstratta::create([
                            'id' => Str::uuid(),
                            'thread_email_id' => $threadEmail->id,
                            'ordinamento' => $index,
                            'data_servizio' => $riga['data_servizio'] ?? null,
                            'ora_pickup' => $riga['ora_pickup'] ?? null,
                            'tipo_servizio' => $riga['tipo_servizio'] ?? 'altro',
                            'pickup' => $riga['pickup'] ?? null,
                            'dropoff' => $riga['dropoff'] ?? null,
                            'passeggeri' => $riga['passeggeri'] ?? null,
                            'veicolo_preferito' => $riga['veicolo_preferito'] ?? null,
                            'note' => $riga['note'] ?? null,
                            'confidenza' => $riga['confidenza'] ?? null,
                            'created_at' => now(),
                        ]);
                    }

                    Log::channel('gmail')->info("Extracted " . count($extraction['righe']) . " rows from message", [
                        'richiesta_id' => $richiestaId,
                        'thread_email_id' => $threadEmail->id,
                    ]);
                } else {
                    Log::channel('gmail')->info("LLM extraction returned no rows", [
                        'richiesta_id' => $richiestaId,
                        'thread_email_id' => $threadEmail->id,
                    ]);
                }
            }
        }

        return [
            'status' => $isNewRichiesta ? 'new_richiesta' : 'thread_update',
            'richiesta_id' => $richiestaId,
            'thread_email_id' => $threadEmail->id,
            'extraction' => $extraction,
        ];
    }

    /**
     * Find an existing contact or create a new one.
     * Lookup order: users table → contacts table → create new contact.
     */
    private function findOrCreateContact(int $companyId, string $email, ?string $name): Contact
    {
        // 1. Check if user exists with this email
        $user = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('email', $email)
            ->first();

        if ($user) {
            // Find or create contact linked to this user
            $contact = Contact::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('email', $email)
                ->first();

            if ($contact) {
                if (!$contact->user_id) {
                    $contact->update(['user_id' => $user->id]);
                }
                return $contact;
            }

            return Contact::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'name' => $name ?? $user->name . ' ' . ($user->surname ?? ''),
                'email' => $email,
                'phone' => $user->phone,
            ]);
        }

        // 2. Check if contact exists
        $contact = Contact::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('email', $email)
            ->first();

        if ($contact) {
            return $contact;
        }

        // 3. Create new contact
        return Contact::create([
            'company_id' => $companyId,
            'name' => $name ?? $email,
            'email' => $email,
        ]);
    }

    /**
     * Extract email address from a "Name <email@example.com>" string.
     */
    private function extractEmailAddress(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $matches)) {
            return strtolower(trim($matches[1]));
        }
        return strtolower(trim($from));
    }

    /**
     * Extract display name from a "Name <email@example.com>" string.
     */
    private function extractName(string $from): ?string
    {
        if (preg_match('/^(.+?)\s*</', $from, $matches)) {
            $name = trim($matches[1], ' "\'');
            return !empty($name) ? $name : null;
        }
        return null;
    }
}
