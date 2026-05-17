<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GmailAccount;
use App\Models\Richiesta;
use App\Models\RigaEstratta;
use App\Models\RigaRichiesta;
use App\Models\ThreadEmail;
use App\Services\GmailService;
use App\Services\RichiestaMergeService;
use App\Services\RichiestaSplitService;
use App\Services\RichiestaStateMachineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RichiestaController extends Controller
{
    public function index(Request $request)
    {
        $query = Richiesta::with(['contact', 'operatore', 'righe', 'quotes' => function ($q) {
                $q->where('is_active_version', true)->select('id', 'richiesta_id', 'status', 'version', 'is_active_version');
            }])
            ->withCount('righe', 'threadEmails', 'quotes');

        // Filters
        if ($request->filled('stato')) {
            $stati = explode(',', $request->stato);
            if (count($stati) > 1) {
                $query->whereIn('stato', $stati);
            } else {
                $query->where('stato', $request->stato);
            }
        }

        if ($request->filled('fonte')) {
            $query->where('fonte', $request->fonte);
        }

        if ($request->filled('operatore_id')) {
            $query->where('operatore_id', $request->operatore_id);
        }

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        if ($request->filled('date_from')) {
            $query->where('data_ricezione', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('data_ricezione', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', function ($cq) use ($search) {
                    $cq->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                })
                ->orWhere('note', 'ilike', "%{$search}%")
                ->orWhereHas('righe', function ($rq) use ($search) {
                    $rq->where('pickup', 'ilike', "%{$search}%")
                        ->orWhere('dropoff', 'ilike', "%{$search}%");
                });
            });
        }

        // Sorting — unread first, then by chosen field
        $sortBy = $request->get('sort_by', 'data_ricezione');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderByRaw('CASE WHEN ultimo_messaggio_inbound_at > COALESCE(ultimo_messaggio_letto_at, \'1970-01-01\') THEN 0 ELSE 1 END')
              ->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $richieste = $query->paginate($perPage);

        return response()->json($richieste);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'fonte' => 'required|in:email,web_form,telefono,manuale',
            'data_ricezione' => 'nullable|date',
            'note' => 'nullable|string',
            'operatore_id' => 'nullable|exists:users,id',
            'righe' => 'nullable|array',
            'righe.*.data_servizio' => 'required|date',
            'righe.*.ora_pickup' => 'nullable|date_format:H:i',
            'righe.*.tipo_servizio' => 'required|in:trasferimento,tour,esperienza,altro',
            'righe.*.pickup' => 'required|string|max:500',
            'righe.*.dropoff' => 'required|string|max:500',
            'righe.*.passeggeri' => 'nullable|integer|min:1',
            'righe.*.veicolo_preferito' => 'nullable|string|max:255',
            'righe.*.note' => 'nullable|string',
        ]);

        $user = Auth::user();
        $companyId = $user->role === 'super-admin' && $request->has('company_id')
            ? $request->company_id
            : $user->company_id;

        $richiesta = Richiesta::create([
            'id' => Str::uuid(),
            'company_id' => $companyId,
            'contact_id' => $validated['contact_id'],
            'fonte' => $validated['fonte'],
            'stato' => Richiesta::STATO_NUOVA,
            'data_ricezione' => $validated['data_ricezione'] ?? now(),
            'note' => $validated['note'] ?? null,
            'operatore_id' => $validated['operatore_id'] ?? $user->id,
        ]);

        // Create righe if provided
        if (!empty($validated['righe'])) {
            foreach ($validated['righe'] as $index => $riga) {
                RigaRichiesta::create([
                    'id' => Str::uuid(),
                    'richiesta_id' => $richiesta->id,
                    'ordinamento' => $index,
                    'data_servizio' => $riga['data_servizio'],
                    'ora_pickup' => $riga['ora_pickup'] ?? null,
                    'tipo_servizio' => $riga['tipo_servizio'],
                    'pickup' => $riga['pickup'],
                    'dropoff' => $riga['dropoff'],
                    'passeggeri' => $riga['passeggeri'] ?? null,
                    'veicolo_preferito' => $riga['veicolo_preferito'] ?? null,
                    'note' => $riga['note'] ?? null,
                    'created_at' => now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Richiesta creata con successo',
            'data' => $richiesta->load(['contact', 'operatore', 'righe']),
        ], 201);
    }

    public function show(string $id)
    {
        $richiesta = Richiesta::with([
            'contact',
            'operatore',
            'righe',
            'threadEmails.mailbox',
            'quotes.items',
            'origine',
            'figlie',
        ])->findOrFail($id);

        return response()->json(['data' => $richiesta]);
    }

    public function update(Request $request, string $id)
    {
        $richiesta = Richiesta::findOrFail($id);

        $validated = $request->validate([
            'contact_id' => 'sometimes|exists:contacts,id',
            'fonte' => 'sometimes|in:email,web_form,telefono,manuale',
            'data_ricezione' => 'nullable|date',
            'note' => 'nullable|string',
            'operatore_id' => 'nullable|exists:users,id',
            'righe' => 'nullable|array',
            'righe.*.id' => 'nullable|uuid',
            'righe.*.data_servizio' => 'required|date',
            'righe.*.ora_pickup' => 'nullable|date_format:H:i',
            'righe.*.tipo_servizio' => 'required|in:trasferimento,tour,esperienza,altro',
            'righe.*.pickup' => 'required|string|max:500',
            'righe.*.dropoff' => 'required|string|max:500',
            'righe.*.passeggeri' => 'nullable|integer|min:1',
            'righe.*.veicolo_preferito' => 'nullable|string|max:255',
            'righe.*.note' => 'nullable|string',
        ]);

        $richiesta->update(collect($validated)->except('righe')->toArray());

        // Sync righe if provided
        if (array_key_exists('righe', $validated)) {
            $existingIds = [];
            foreach ($validated['righe'] as $index => $rigaData) {
                $rigaId = $rigaData['id'] ?? null;
                if ($rigaId) {
                    // Update existing
                    $riga = RigaRichiesta::where('richiesta_id', $richiesta->id)->find($rigaId);
                    if ($riga) {
                        $riga->update([
                            'ordinamento' => $index,
                            'data_servizio' => $rigaData['data_servizio'],
                            'ora_pickup' => $rigaData['ora_pickup'] ?? null,
                            'tipo_servizio' => $rigaData['tipo_servizio'],
                            'pickup' => $rigaData['pickup'],
                            'dropoff' => $rigaData['dropoff'],
                            'passeggeri' => $rigaData['passeggeri'] ?? null,
                            'veicolo_preferito' => $rigaData['veicolo_preferito'] ?? null,
                            'note' => $rigaData['note'] ?? null,
                        ]);
                        $existingIds[] = $rigaId;
                    }
                } else {
                    // Create new
                    $newRiga = RigaRichiesta::create([
                        'id' => Str::uuid(),
                        'richiesta_id' => $richiesta->id,
                        'ordinamento' => $index,
                        'data_servizio' => $rigaData['data_servizio'],
                        'ora_pickup' => $rigaData['ora_pickup'] ?? null,
                        'tipo_servizio' => $rigaData['tipo_servizio'],
                        'pickup' => $rigaData['pickup'],
                        'dropoff' => $rigaData['dropoff'],
                        'passeggeri' => $rigaData['passeggeri'] ?? null,
                        'veicolo_preferito' => $rigaData['veicolo_preferito'] ?? null,
                        'note' => $rigaData['note'] ?? null,
                        'created_at' => now(),
                    ]);
                    $existingIds[] = $newRiga->id;
                }
            }

            // Delete removed righe
            RigaRichiesta::where('richiesta_id', $richiesta->id)
                ->whereNotIn('id', $existingIds)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Richiesta aggiornata con successo',
            'data' => $richiesta->fresh()->load(['contact', 'operatore', 'righe']),
        ]);
    }

    public function destroy(string $id)
    {
        $richiesta = Richiesta::findOrFail($id);
        $richiesta->delete();

        return response()->json([
            'success' => true,
            'message' => 'Richiesta eliminata con successo',
        ]);
    }

    /**
     * Transition the richiesta to a new state.
     */
    public function transition(Request $request, string $id)
    {
        $richiesta = Richiesta::findOrFail($id);

        $validated = $request->validate([
            'stato' => 'required|string',
            'motivo' => 'nullable|string',
        ]);

        $service = new RichiestaStateMachineService();
        $richiesta = $service->transition($richiesta, $validated['stato'], $validated['motivo'] ?? null);

        return response()->json([
            'success' => true,
            'message' => "Stato aggiornato a '{$validated['stato']}'",
            'data' => $richiesta->fresh()->load(['contact', 'operatore', 'righe']),
        ]);
    }

    /**
     * Get allowed manual transitions for a richiesta.
     */
    public function getTransitions(string $id)
    {
        $richiesta = Richiesta::findOrFail($id);
        $service = new RichiestaStateMachineService();

        return response()->json([
            'stato_corrente' => $richiesta->stato,
            'transizioni_possibili' => $service->allowedManualTransitions($richiesta->stato),
        ]);
    }

    /**
     * Mark a richiesta's messages as read.
     */
    public function markRead(string $id)
    {
        $richiesta = Richiesta::findOrFail($id);
        $richiesta->update(['ultimo_messaggio_letto_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Take charge of a richiesta (nuova → in_lavorazione).
     */
    public function takeCharge(string $id)
    {
        $richiesta = Richiesta::findOrFail($id);
        $service = new RichiestaStateMachineService();
        $richiesta = $service->takeCharge($richiesta);

        return response()->json([
            'success' => true,
            'data' => $richiesta->fresh()->load(['contact', 'operatore', 'righe']),
        ]);
    }

    /**
     * Get thread emails for a richiesta.
     */
    public function getThreadEmails(string $id)
    {
        $richiesta = Richiesta::findOrFail($id);

        $emails = ThreadEmail::withoutGlobalScopes()
            ->with('righeEstratte')
            ->where('richiesta_id', $richiesta->id)
            ->orderBy('ricevuto_at', 'asc')
            ->get();

        return response()->json(['data' => $emails]);
    }

    /**
     * Reply to a thread email within a richiesta.
     */
    public function reply(Request $request, string $id)
    {
        $richiesta = Richiesta::with('threadEmails')->findOrFail($id);

        $validated = $request->validate([
            'gmail_account_id' => 'required|exists:gmail_accounts,id',
            'to' => 'required|email',
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'in_reply_to_rfc' => 'nullable|string',
            'thread_id_gmail' => 'nullable|string',
        ]);

        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $validated['gmail_account_id'])
            ->where('company_id', $richiesta->company_id)
            ->firstOrFail();

        $gmailService = new GmailService($account);

        $result = $gmailService->sendDirect(
            $validated['to'],
            $validated['subject'],
            $validated['body_html']
        );

        // Save outbound message in thread_emails
        $threadEmail = ThreadEmail::create([
            'id' => Str::uuid(),
            'company_id' => $richiesta->company_id,
            'richiesta_id' => $richiesta->id,
            'mailbox_id' => $account->id,
            'thread_id_gmail' => $result['thread_id'] ?? $validated['thread_id_gmail'] ?? '',
            'message_id_rfc' => '<sent-' . Str::uuid() . '@' . $account->email_address . '>',
            'in_reply_to_rfc' => $validated['in_reply_to_rfc'],
            'direzione' => 'outbound',
            'mittente' => $account->email_address,
            'destinatario' => $validated['to'],
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'ricevuto_at' => now(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email inviata con successo',
            'data' => $threadEmail,
        ]);
    }

    /**
     * Generate an AI draft reply for a richiesta.
     */
    public function draftAi(Request $request, string $id)
    {
        $richiesta = Richiesta::with(['contact', 'righe', 'threadEmails'])->findOrFail($id);

        $validated = $request->validate([
            'lingua' => 'required|string|max:5',
            'contesto' => 'nullable|string',
        ]);

        $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Chiave API Anthropic non configurata',
            ], 422);
        }

        // Build context from thread and righe
        $context = $this->buildAiContext($richiesta, $validated['lingua'], $validated['contesto'] ?? null);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 2048,
                'messages' => [
                    ['role' => 'user', 'content' => $context],
                ],
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore API: ' . $response->status(),
                ], 422);
            }

            $draft = $response->json('content.0.text', '');

            return response()->json([
                'success' => true,
                'data' => ['draft' => $draft],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add an extracted row to the richiesta plan (righe_richiesta).
     */
    public function addRigaFromEstratta(Request $request, string $id)
    {
        $richiesta = Richiesta::findOrFail($id);

        $validated = $request->validate([
            'riga_estratta_id' => 'required|uuid|exists:righe_estratte,id',
        ]);

        $rigaEstratta = RigaEstratta::findOrFail($validated['riga_estratta_id']);

        // Create riga_richiesta from extracted data
        $nextOrder = RigaRichiesta::where('richiesta_id', $richiesta->id)->max('ordinamento') ?? -1;

        $rigaRichiesta = RigaRichiesta::create([
            'id' => Str::uuid(),
            'richiesta_id' => $richiesta->id,
            'ordinamento' => $nextOrder + 1,
            'data_servizio' => $rigaEstratta->data_servizio,
            'ora_pickup' => $rigaEstratta->ora_pickup,
            'tipo_servizio' => $rigaEstratta->tipo_servizio,
            'pickup' => $rigaEstratta->pickup,
            'dropoff' => $rigaEstratta->dropoff,
            'passeggeri' => $rigaEstratta->passeggeri,
            'veicolo_preferito' => $rigaEstratta->veicolo_preferito,
            'note' => $rigaEstratta->note,
            'confidenza' => $rigaEstratta->confidenza,
            'riga_estratta_origine_id' => $rigaEstratta->id,
            'created_at' => now(),
        ]);

        // Link back
        $rigaEstratta->update(['riga_richiesta_id' => $rigaRichiesta->id]);

        return response()->json([
            'success' => true,
            'message' => 'Riga aggiunta al piano',
            'data' => $rigaRichiesta,
        ]);
    }

    /**
     * Update an existing riga_richiesta with data from a riga_estratta.
     */
    public function updateRigaFromEstratta(Request $request, string $id)
    {
        $richiesta = Richiesta::findOrFail($id);

        $validated = $request->validate([
            'riga_richiesta_id' => 'required|uuid|exists:righe_richiesta,id',
            'riga_estratta_id' => 'required|uuid|exists:righe_estratte,id',
        ]);

        $rigaRichiesta = RigaRichiesta::where('richiesta_id', $richiesta->id)
            ->findOrFail($validated['riga_richiesta_id']);
        $rigaEstratta = RigaEstratta::findOrFail($validated['riga_estratta_id']);

        // Update with extracted data (only non-null fields)
        $updates = [];
        if ($rigaEstratta->data_servizio) $updates['data_servizio'] = $rigaEstratta->data_servizio;
        if ($rigaEstratta->ora_pickup) $updates['ora_pickup'] = $rigaEstratta->ora_pickup;
        if ($rigaEstratta->tipo_servizio) $updates['tipo_servizio'] = $rigaEstratta->tipo_servizio;
        if ($rigaEstratta->pickup) $updates['pickup'] = $rigaEstratta->pickup;
        if ($rigaEstratta->dropoff) $updates['dropoff'] = $rigaEstratta->dropoff;
        if ($rigaEstratta->passeggeri) $updates['passeggeri'] = $rigaEstratta->passeggeri;
        if ($rigaEstratta->veicolo_preferito) $updates['veicolo_preferito'] = $rigaEstratta->veicolo_preferito;
        if ($rigaEstratta->note) $updates['note'] = $rigaEstratta->note;

        $rigaRichiesta->update($updates);

        // Link back
        $rigaEstratta->update(['riga_richiesta_id' => $rigaRichiesta->id]);

        return response()->json([
            'success' => true,
            'message' => 'Riga aggiornata dal messaggio',
            'data' => $rigaRichiesta->fresh(),
        ]);
    }

    /**
     * Add a manual row to the richiesta plan.
     */
    public function addRigaManuale(Request $request, string $id)
    {
        $richiesta = Richiesta::findOrFail($id);

        $validated = $request->validate([
            'data_servizio' => 'nullable|date',
            'ora_pickup' => 'nullable|date_format:H:i',
            'tipo_servizio' => 'required|in:trasferimento,tour,esperienza,altro',
            'pickup' => 'nullable|string|max:500',
            'dropoff' => 'nullable|string|max:500',
            'passeggeri' => 'nullable|integer|min:1',
            'veicolo_preferito' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $nextOrder = RigaRichiesta::where('richiesta_id', $richiesta->id)->max('ordinamento') ?? -1;

        $riga = RigaRichiesta::create([
            'id' => Str::uuid(),
            'richiesta_id' => $richiesta->id,
            'ordinamento' => $nextOrder + 1,
            'data_servizio' => $validated['data_servizio'] ?? null,
            'ora_pickup' => $validated['ora_pickup'] ?? null,
            'tipo_servizio' => $validated['tipo_servizio'],
            'pickup' => $validated['pickup'] ?? null,
            'dropoff' => $validated['dropoff'] ?? null,
            'passeggeri' => $validated['passeggeri'] ?? null,
            'veicolo_preferito' => $validated['veicolo_preferito'] ?? null,
            'note' => $validated['note'] ?? null,
            'modificata_manualmente' => true,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Riga aggiunta manualmente',
            'data' => $riga,
        ]);
    }

    /**
     * Update a riga_richiesta manually.
     */
    public function updateRiga(Request $request, string $id, string $rigaId)
    {
        $richiesta = Richiesta::findOrFail($id);
        $riga = RigaRichiesta::where('richiesta_id', $richiesta->id)->findOrFail($rigaId);

        $validated = $request->validate([
            'data_servizio' => 'nullable|date',
            'ora_pickup' => 'nullable|date_format:H:i',
            'tipo_servizio' => 'sometimes|in:trasferimento,tour,esperienza,altro',
            'pickup' => 'nullable|string|max:500',
            'dropoff' => 'nullable|string|max:500',
            'passeggeri' => 'nullable|integer|min:1',
            'veicolo_preferito' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $validated['modificata_manualmente'] = true;
        $riga->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Riga aggiornata',
            'data' => $riga->fresh(),
        ]);
    }

    /**
     * Remove a riga_richiesta from the plan.
     */
    public function removeRiga(string $id, string $rigaId)
    {
        $richiesta = Richiesta::findOrFail($id);
        $riga = RigaRichiesta::where('richiesta_id', $richiesta->id)->findOrFail($rigaId);

        // Unlink any righe_estratte that pointed to this
        RigaEstratta::where('riga_richiesta_id', $riga->id)->update(['riga_richiesta_id' => null]);

        $riga->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riga rimossa dal piano',
        ]);
    }

    /**
     * Manually link a thread email to a richiesta.
     */
    public function collegaThread(Request $request, string $threadEmailId)
    {
        $validated = $request->validate([
            'richiesta_id' => 'required|uuid',
        ]);

        $threadEmail = ThreadEmail::withoutGlobalScopes()->findOrFail($threadEmailId);
        $richiesta = Richiesta::findOrFail($validated['richiesta_id']);

        if ($threadEmail->company_id !== $richiesta->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Thread email e richiesta devono appartenere alla stessa azienda.',
            ], 422);
        }

        $threadEmail->update(['richiesta_id' => $richiesta->id]);

        return response()->json([
            'success' => true,
            'message' => 'Thread collegato alla richiesta',
            'data' => $threadEmail->fresh(),
        ]);
    }

    /**
     * Unlink a thread email from its richiesta.
     */
    public function scollegaThread(string $threadEmailId)
    {
        $threadEmail = ThreadEmail::withoutGlobalScopes()->findOrFail($threadEmailId);
        $threadEmail->update(['richiesta_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Thread scollegato dalla richiesta',
            'data' => $threadEmail->fresh(),
        ]);
    }

    /**
     * Get unlinked thread emails (not associated to any richiesta).
     */
    public function unlinkedThreadEmails(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->role === 'super-admin' && $request->has('company_id')
            ? $request->company_id
            : $user->company_id;

        $emails = ThreadEmail::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('richiesta_id')
            ->orderBy('ricevuto_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['data' => $emails]);
    }

    /**
     * Split a richiesta into two.
     */
    public function split(Request $request, string $id)
    {
        $richiesta = Richiesta::findOrFail($id);

        $validated = $request->validate([
            'righe_figlia_1' => 'required|array|min:1',
            'righe_figlia_1.*' => 'uuid',
            'righe_figlia_2' => 'required|array|min:1',
            'righe_figlia_2.*' => 'uuid',
        ]);

        try {
            $service = new RichiestaSplitService();
            [$figlia1, $figlia2] = $service->split(
                $richiesta,
                $validated['righe_figlia_1'],
                $validated['righe_figlia_2']
            );

            return response()->json([
                'success' => true,
                'message' => 'Richiesta divisa con successo',
                'data' => [
                    'figlia_1' => $figlia1->load(['contact', 'righe']),
                    'figlia_2' => $figlia2->load(['contact', 'righe']),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Merge two richieste into one.
     */
    public function merge(Request $request)
    {
        $validated = $request->validate([
            'richiesta_id_1' => 'required|uuid',
            'richiesta_id_2' => 'required|uuid',
        ]);

        $richiesta1 = Richiesta::findOrFail($validated['richiesta_id_1']);
        $richiesta2 = Richiesta::findOrFail($validated['richiesta_id_2']);

        try {
            $service = new RichiestaMergeService();
            $merged = $service->merge($richiesta1, $richiesta2);

            return response()->json([
                'success' => true,
                'message' => 'Richieste unite con successo',
                'data' => $merged->load(['contact', 'righe', 'threadEmails']),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function buildAiContext(Richiesta $richiesta, string $lingua, ?string $contesto): string
    {
        $contact = $richiesta->contact;
        $righe = $richiesta->righe;
        $lastEmails = $richiesta->threadEmails->take(-5);

        $threadSummary = '';
        foreach ($lastEmails as $email) {
            $dir = $email->direzione === 'inbound' ? 'CLIENTE' : 'OPERATORE';
            $body = $email->body_text ?? strip_tags($email->body_html ?? '');
            $body = mb_substr($body, 0, 500);
            $threadSummary .= "[{$dir}] {$email->subject}\n{$body}\n\n";
        }

        $righeSummary = '';
        foreach ($righe as $riga) {
            $righeSummary .= "- {$riga->data_servizio->format('d/m/Y')}: {$riga->pickup} → {$riga->dropoff} ({$riga->tipo_servizio})\n";
        }

        $langNames = [
            'it' => 'italiano', 'en' => 'inglese', 'de' => 'tedesco',
            'fr' => 'francese', 'es' => 'spagnolo',
        ];
        $langName = $langNames[$lingua] ?? $lingua;

        return <<<PROMPT
Scrivi una risposta email professionale in {$langName} per un servizio NCC (noleggio con conducente).

Cliente: {$contact->name} ({$contact->email})

Servizi richiesti:
{$righeSummary}

Conversazione recente:
{$threadSummary}

Istruzioni aggiuntive dell'operatore: {$contesto}

Scrivi SOLO il corpo dell'email (HTML), senza subject. Tono professionale ma cordiale. Non inventare prezzi o dettagli non presenti.
PROMPT;
    }
}
