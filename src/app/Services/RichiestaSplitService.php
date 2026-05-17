<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Richiesta;
use App\Models\RigaRichiesta;
use App\Models\ThreadEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RichiestaSplitService
{
    /**
     * Split a richiesta into two, distributing righe between them.
     *
     * @param Richiesta $richiesta The richiesta to split
     * @param array $righeIdsFiglia1 IDs of righe for the first new richiesta
     * @param array $righeIdsFiglia2 IDs of righe for the second new richiesta
     * @return array The two new richieste
     * @throws \RuntimeException
     */
    public function split(Richiesta $richiesta, array $righeIdsFiglia1, array $righeIdsFiglia2): array
    {
        // Validate prerequisites
        $hasConfirmedQuote = Quote::withoutGlobalScopes()
            ->where('richiesta_id', $richiesta->id)
            ->where('status', Quote::STATUS_DEPOSIT_RECEIVED)
            ->exists();

        if ($hasConfirmedQuote) {
            throw new \RuntimeException('Impossibile dividere: la richiesta ha un preventivo confermato.');
        }

        if (empty($righeIdsFiglia1) || empty($righeIdsFiglia2)) {
            throw new \RuntimeException('Entrambe le richieste figlie devono avere almeno una riga.');
        }

        return DB::transaction(function () use ($richiesta, $righeIdsFiglia1, $righeIdsFiglia2) {
            // Create first daughter
            $figlia1 = $this->createFiglia($richiesta, $righeIdsFiglia1);

            // Create second daughter
            $figlia2 = $this->createFiglia($richiesta, $righeIdsFiglia2);

            // Copy thread emails to both daughters
            $threadEmails = ThreadEmail::withoutGlobalScopes()
                ->where('richiesta_id', $richiesta->id)
                ->get();

            foreach ($threadEmails as $thread) {
                // Copy to figlia1
                ThreadEmail::create([
                    'id' => Str::uuid(),
                    'company_id' => $thread->company_id,
                    'richiesta_id' => $figlia1->id,
                    'mailbox_id' => $thread->mailbox_id,
                    'thread_id_gmail' => $thread->thread_id_gmail,
                    'message_id_rfc' => $thread->message_id_rfc . '-split1',
                    'in_reply_to_rfc' => $thread->in_reply_to_rfc,
                    'references_rfc' => $thread->references_rfc,
                    'direzione' => $thread->direzione,
                    'mittente' => $thread->mittente,
                    'destinatario' => $thread->destinatario,
                    'subject' => $thread->subject,
                    'body_text' => $thread->body_text,
                    'body_html' => $thread->body_html,
                    'ricevuto_at' => $thread->ricevuto_at,
                    'created_at' => now(),
                ]);

                // Copy to figlia2
                ThreadEmail::create([
                    'id' => Str::uuid(),
                    'company_id' => $thread->company_id,
                    'richiesta_id' => $figlia2->id,
                    'mailbox_id' => $thread->mailbox_id,
                    'thread_id_gmail' => $thread->thread_id_gmail,
                    'message_id_rfc' => $thread->message_id_rfc . '-split2',
                    'in_reply_to_rfc' => $thread->in_reply_to_rfc,
                    'references_rfc' => $thread->references_rfc,
                    'direzione' => $thread->direzione,
                    'mittente' => $thread->mittente,
                    'destinatario' => $thread->destinatario,
                    'subject' => $thread->subject,
                    'body_text' => $thread->body_text,
                    'body_html' => $thread->body_html,
                    'ricevuto_at' => $thread->ricevuto_at,
                    'created_at' => now(),
                ]);
            }

            // Annul draft quotes on the original
            Quote::withoutGlobalScopes()
                ->where('richiesta_id', $richiesta->id)
                ->where('status', Quote::STATUS_DRAFT)
                ->update(['status' => 'annullato']);

            // Mark original as annullata
            $richiesta->update([
                'stato' => Richiesta::STATO_ANNULLATA,
                'relazione' => Richiesta::RELAZIONE_SDOPPIATA_DA,
            ]);

            return [$figlia1, $figlia2];
        });
    }

    private function createFiglia(Richiesta $origine, array $righeIds): Richiesta
    {
        $figlia = Richiesta::create([
            'id' => Str::uuid(),
            'company_id' => $origine->company_id,
            'contact_id' => $origine->contact_id,
            'fonte' => $origine->fonte,
            'stato' => Richiesta::STATO_IN_LAVORAZIONE,
            'data_ricezione' => $origine->data_ricezione,
            'note' => $origine->note,
            'origine_id' => $origine->id,
            'operatore_id' => $origine->operatore_id,
        ]);

        // Move righe to new richiesta
        RigaRichiesta::whereIn('id', $righeIds)
            ->where('richiesta_id', $origine->id)
            ->update(['richiesta_id' => $figlia->id]);

        // Reorder
        $righe = RigaRichiesta::where('richiesta_id', $figlia->id)->orderBy('ordinamento')->get();
        foreach ($righe as $index => $riga) {
            $riga->update(['ordinamento' => $index]);
        }

        return $figlia;
    }
}
