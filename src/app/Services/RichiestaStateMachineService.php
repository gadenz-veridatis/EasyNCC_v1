<?php

namespace App\Services;

use App\Models\Richiesta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RichiestaStateMachineService
{
    /**
     * All valid transitions (both automatic and manual).
     */
    private array $transitions = [
        Richiesta::STATO_NUOVA => [
            Richiesta::STATO_IN_LAVORAZIONE,
            Richiesta::STATO_ANNULLATA,
            Richiesta::STATO_SOSPESA,
        ],
        Richiesta::STATO_IN_LAVORAZIONE => [
            Richiesta::STATO_PREVENTIVATA,
            Richiesta::STATO_ANNULLATA,
            Richiesta::STATO_SOSPESA,
        ],
        Richiesta::STATO_PREVENTIVATA => [
            Richiesta::STATO_CONFERMATA,
            Richiesta::STATO_IN_LAVORAZIONE,
            Richiesta::STATO_ANNULLATA,
            Richiesta::STATO_SOSPESA,
        ],
        Richiesta::STATO_CONFERMATA => [
            Richiesta::STATO_ANNULLATA,
        ],
        Richiesta::STATO_ANNULLATA => [],
        Richiesta::STATO_SOSPESA => [
            Richiesta::STATO_IN_LAVORAZIONE,
            Richiesta::STATO_ANNULLATA,
        ],
    ];

    /**
     * Transitions that should NOT appear as manual buttons.
     * These happen only as consequence of other actions.
     */
    private array $automaticTransitions = [
        'preventivata',  // triggered by quote creation
        'confermata',    // triggered by deposit payment webhook
    ];

    /**
     * Transition the richiesta to a new state.
     *
     * @throws \InvalidArgumentException
     */
    public function transition(Richiesta $richiesta, string $nuovoStato, ?string $motivo = null): Richiesta
    {
        $statoCorrente = $richiesta->stato;

        if (!$this->canTransition($statoCorrente, $nuovoStato)) {
            throw new \InvalidArgumentException(
                "Transizione non valida da '{$statoCorrente}' a '{$nuovoStato}'"
            );
        }

        $richiesta->update(['stato' => $nuovoStato]);

        Log::channel('gmail')->info("Richiesta {$richiesta->id}: {$statoCorrente} → {$nuovoStato}", [
            'motivo' => $motivo,
        ]);

        return $richiesta;
    }

    /**
     * Take charge of a richiesta: nuova → in_lavorazione + assign operator.
     */
    public function takeCharge(Richiesta $richiesta): Richiesta
    {
        if ($richiesta->stato !== Richiesta::STATO_NUOVA) {
            return $richiesta; // Already taken, no error
        }

        $user = Auth::user();

        $richiesta->update([
            'stato' => Richiesta::STATO_IN_LAVORAZIONE,
            'operatore_id' => $richiesta->operatore_id ?? $user?->id,
        ]);

        Log::channel('gmail')->info("Richiesta {$richiesta->id}: presa in carico da {$user?->id}");

        return $richiesta;
    }

    /**
     * Check if a transition is allowed.
     */
    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, $this->transitions[$from] ?? []);
    }

    /**
     * Get allowed manual transitions from current state.
     * Excludes automatic transitions (preventivata, confermata).
     */
    public function allowedManualTransitions(string $from): array
    {
        $all = $this->transitions[$from] ?? [];
        return array_values(array_filter($all, fn($t) => !in_array($t, $this->automaticTransitions)));
    }

    /**
     * Get all allowed transitions from current state (for internal use).
     */
    public function allowedTransitions(string $from): array
    {
        return $this->transitions[$from] ?? [];
    }
}
