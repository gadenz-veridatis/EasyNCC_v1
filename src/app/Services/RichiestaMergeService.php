<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Richiesta;
use App\Models\RigaRichiesta;
use App\Models\ThreadEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RichiestaMergeService
{
    /**
     * Merge two richieste into one.
     *
     * @param Richiesta $richiesta1 First richiesta
     * @param Richiesta $richiesta2 Second richiesta
     * @return Richiesta The merged richiesta
     * @throws \RuntimeException
     */
    public function merge(Richiesta $richiesta1, Richiesta $richiesta2): Richiesta
    {
        // Validate prerequisites
        foreach ([$richiesta1, $richiesta2] as $r) {
            $hasConfirmedQuote = Quote::withoutGlobalScopes()
                ->where('richiesta_id', $r->id)
                ->where('status', Quote::STATUS_DEPOSIT_RECEIVED)
                ->exists();

            if ($hasConfirmedQuote) {
                throw new \RuntimeException("Impossibile unire: la richiesta {$r->id} ha un preventivo confermato.");
            }
        }

        if ($richiesta1->company_id !== $richiesta2->company_id) {
            throw new \RuntimeException('Le richieste devono appartenere alla stessa azienda.');
        }

        return DB::transaction(function () use ($richiesta1, $richiesta2) {
            // Create unified richiesta
            $merged = Richiesta::create([
                'id' => Str::uuid(),
                'company_id' => $richiesta1->company_id,
                'contact_id' => $richiesta1->contact_id,
                'fonte' => $richiesta1->fonte,
                'stato' => Richiesta::STATO_IN_LAVORAZIONE,
                'data_ricezione' => min($richiesta1->data_ricezione, $richiesta2->data_ricezione),
                'note' => trim(($richiesta1->note ?? '') . "\n" . ($richiesta2->note ?? '')) ?: null,
                'operatore_id' => $richiesta1->operatore_id,
            ]);

            // Move all righe from both to merged
            RigaRichiesta::where('richiesta_id', $richiesta1->id)
                ->update(['richiesta_id' => $merged->id]);

            RigaRichiesta::where('richiesta_id', $richiesta2->id)
                ->update(['richiesta_id' => $merged->id]);

            // Reorder all righe by data_servizio
            $righe = RigaRichiesta::where('richiesta_id', $merged->id)
                ->orderBy('data_servizio')
                ->orderBy('ora_pickup')
                ->get();

            foreach ($righe as $index => $riga) {
                $riga->update(['ordinamento' => $index]);
            }

            // Move all thread emails to merged
            ThreadEmail::withoutGlobalScopes()
                ->where('richiesta_id', $richiesta1->id)
                ->update(['richiesta_id' => $merged->id]);

            ThreadEmail::withoutGlobalScopes()
                ->where('richiesta_id', $richiesta2->id)
                ->update(['richiesta_id' => $merged->id]);

            // Annul draft quotes on both originals
            Quote::withoutGlobalScopes()
                ->whereIn('richiesta_id', [$richiesta1->id, $richiesta2->id])
                ->where('status', Quote::STATUS_DRAFT)
                ->update(['status' => 'annullato']);

            // Mark originals as annullata
            $richiesta1->update([
                'stato' => Richiesta::STATO_ANNULLATA,
                'relazione' => Richiesta::RELAZIONE_UNITA_CON,
                'origine_id' => $merged->id,
            ]);

            $richiesta2->update([
                'stato' => Richiesta::STATO_ANNULLATA,
                'relazione' => Richiesta::RELAZIONE_UNITA_CON,
                'origine_id' => $merged->id,
            ]);

            return $merged;
        });
    }
}
