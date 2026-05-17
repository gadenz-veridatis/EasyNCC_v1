<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteStateTransition;
use App\Models\Richiesta;
use App\Services\PricingCalculatorService;
use App\Services\QuoteStateMachineService;
use App\Services\RichiestaStateMachineService;
use App\Services\QuoteTemplateService;
use App\Models\QuoteEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Quote::query()->activeVersions();

        // Multi-tenancy for super-admin
        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $query->withoutGlobalScopes()->where('company_id', $request->company_id)->where('is_active_version', true);
        }

        // Filter by contact
        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'ilike', "%{$search}%")
                  ->orWhere('destination_name', 'ilike', "%{$search}%")
                  ->orWhere('service_type', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%")
                  ->orWhereHas('items', function ($itemQ) use ($search) {
                      $itemQ->where('destination_name', 'ilike', "%{$search}%")
                            ->orWhere('service_type', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filters
        if ($request->filled('service_type')) {
            $query->where(function ($q) use ($request) {
                $q->where('service_type', $request->service_type)
                  ->orWhereHas('items', function ($itemQ) use ($request) {
                      $itemQ->where('service_type', $request->service_type);
                  });
            });
        }
        if ($request->filled('seasonality')) {
            $query->where('seasonality', $request->seasonality);
        }
        if ($request->filled('date_from')) {
            $query->where('service_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('service_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Include soft deleted
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_order', 'desc');
        $allowedSorts = ['id', 'client_name', 'service_date', 'destination_name', 'service_type', 'taxable_price_rounded', 'final_price_rounded', 'client_price', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $quotes = $query->with(['user:id,name,surname', 'creator:id,name,surname', 'contact', 'items'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $quotes->items(),
            'meta' => [
                'current_page' => $quotes->currentPage(),
                'last_page' => $quotes->lastPage(),
                'per_page' => $quotes->perPage(),
                'total' => $quotes->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateQuote($request);

        $user = Auth::user();
        $companyId = ($user->role === 'super-admin' && $request->has('company_id'))
            ? $request->company_id
            : $user->company_id;

        // Validate and extract items
        $itemsData = $this->validateItems($request);

        // Calculate quote totals from items
        $itemsTaxableSum = collect($itemsData)->sum('taxable_price');
        $calculated = PricingCalculatorService::calculateQuoteTotals(array_merge($validated, [
            'items_taxable_sum' => $itemsTaxableSum,
        ]));

        $quote = DB::transaction(function () use ($validated, $calculated, $companyId, $user, $itemsData) {
            $quote = Quote::create(array_merge($validated, $calculated, [
                'company_id' => $companyId,
                'user_id' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]));

            foreach ($itemsData as $index => $item) {
                $quote->items()->create(array_merge($item, ['sort_order' => $index]));
            }

            // Initialize versioning: quote is its own group
            $quote->update(['quote_group_id' => $quote->id]);

            return $quote;
        });

        return response()->json([
            'success' => true,
            'message' => 'Preventivo creato con successo',
            'data' => $quote->load(['user:id,name,surname', 'creator:id,name,surname', 'items']),
        ], 201);
    }

    public function show(Quote $quote)
    {
        return response()->json([
            'data' => $quote->load([
                'user:id,name,surname',
                'creator:id,name,surname',
                'updater:id,name,surname',
                'contact',
                'sumupConfig',
                'gmailAccount',
                'emailTemplate',
                'service:id,pickup_datetime,pickup_location,status_id',
                'stateTransitions' => fn($q) => $q->with('actor:id,name,surname')->orderBy('created_at', 'desc'),
                'items',
            ]),
        ]);
    }

    public function update(Request $request, Quote $quote)
    {
        // Only draft quotes can be edited
        if ($quote->status !== Quote::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Solo i preventivi in stato bozza possono essere modificati',
            ], 422);
        }

        $validated = $this->validateQuote($request, true);

        $user = Auth::user();

        // Validate and extract items
        $itemsData = $this->validateItems($request);

        // Calculate quote totals from items
        $itemsTaxableSum = collect($itemsData)->sum('taxable_price');
        $mergedForCalc = array_merge($quote->toArray(), $validated, [
            'items_taxable_sum' => $itemsTaxableSum,
        ]);
        $calculated = PricingCalculatorService::calculateQuoteTotals($mergedForCalc);

        DB::transaction(function () use ($quote, $validated, $calculated, $user, $itemsData) {
            $quote->update(array_merge($validated, $calculated, [
                'updated_by' => $user->id,
            ]));

            // Sync items: delete old, create new
            $quote->items()->delete();
            foreach ($itemsData as $index => $item) {
                $quote->items()->create(array_merge($item, ['sort_order' => $index]));
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Preventivo aggiornato con successo',
            'data' => $quote->fresh()->load(['user:id,name,surname', 'creator:id,name,surname', 'items']),
        ]);
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();

        return response()->json([
            'success' => true,
            'message' => 'Preventivo eliminato con successo',
        ]);
    }

    public function duplicate(Quote $quote)
    {
        $user = Auth::user();

        $fieldsToCopy = [
            'contact_id', 'client_name', 'client_email', 'service_date', 'notes',
            'vat_percentage', 'card_fees_percentage',
            'override_taxable', 'discount_percentage', 'discount_name',
            'deposit_percentage', 'client_price', 'reference_url',
        ];

        $data = [];
        foreach ($fieldsToCopy as $field) {
            $data[$field] = $quote->$field;
        }

        // Load items from the original quote
        $quote->load('items');
        $itemsData = $quote->items->map(function ($item) {
            return $item->only([
                'pricing_destination_id', 'destination_name', 'service_type',
                'mileage', 'extra_km', 'duration_hours', 'extension_hours',
                'extra_travel_hours', 'toll_cost', 'pax_count',
                'experience_per_pax', 'taxable_price', 'sort_order',
            ]);
        })->toArray();

        // Calculate totals from items
        $itemsTaxableSum = collect($itemsData)->sum('taxable_price');
        $calculated = PricingCalculatorService::calculateQuoteTotals(array_merge($data, [
            'items_taxable_sum' => $itemsTaxableSum,
        ]));

        $newQuote = DB::transaction(function () use ($data, $calculated, $quote, $user, $itemsData) {
            $newQuote = Quote::create(array_merge($data, $calculated, [
                'company_id' => $quote->company_id,
                'user_id' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'status' => Quote::STATUS_DRAFT,
            ]));

            foreach ($itemsData as $item) {
                $newQuote->items()->create($item);
            }

            // New independent group for duplicated quote
            $newQuote->update(['quote_group_id' => $newQuote->id]);

            return $newQuote;
        });

        return response()->json([
            'success' => true,
            'message' => 'Preventivo duplicato con successo',
            'data' => $newQuote->load(['user:id,name,surname', 'creator:id,name,surname', 'items']),
        ], 201);
    }

    public function restore($id)
    {
        $quote = Quote::withTrashed()->findOrFail($id);
        $quote->restore();

        return response()->json([
            'success' => true,
            'message' => 'Preventivo ripristinato con successo',
            'data' => $quote->load(['contact', 'items']),
        ]);
    }

    /**
     * Calculate pricing without saving (real-time preview).
     */
    public function calculate(Request $request)
    {
        $user = Auth::user();
        $companyId = ($user->role === 'super-admin' && $request->has('company_id'))
            ? $request->company_id
            : $user->company_id;

        $config = PricingCalculatorService::getConfig($companyId);
        $result = PricingCalculatorService::calculate($request->all(), $config);

        return response()->json(['data' => $result]);
    }

    /**
     * Execute a state transition on a quote.
     */
    public function transition(Request $request, Quote $quote)
    {
        $request->validate([
            'action' => 'required|string|in:approve,send,revert_to_draft,register_payment',
            'sumup_config_id' => 'nullable|integer',
            'gmail_account_id' => 'nullable|integer',
            'email_template_id' => 'nullable|integer',
            'client_email' => 'nullable|email|max:255',
            'rendered_subject' => 'nullable|string|max:500',
            'rendered_body_html' => 'nullable|string',
            'payment_type_id' => 'nullable|integer|exists:payment_types,id',
            'payment_reference' => 'nullable|string|max:500',
            'payment_date' => 'nullable|date',
        ]);

        $user = Auth::user();
        $stateMachine = new QuoteStateMachineService();

        try {
            $result = match ($request->action) {
                'approve' => $stateMachine->transitionToApproved($quote, $user, [
                    'sumup_config_id' => $request->sumup_config_id,
                    'gmail_account_id' => $request->gmail_account_id,
                    'email_template_id' => $request->email_template_id,
                    'client_email' => $request->client_email,
                ]),
                'send' => $stateMachine->transitionToSent($quote, $user, [
                    'rendered_subject' => $request->rendered_subject,
                    'rendered_body_html' => $request->rendered_body_html,
                ]),
                'revert_to_draft' => $stateMachine->revertToDraft($quote, $user),
                'register_payment' => $stateMachine->transitionToDepositReceived($quote, [
                    'source' => 'manual',
                    'payment_type_id' => $request->payment_type_id,
                    'payment_reference' => $request->payment_reference,
                    'payment_date' => $request->payment_date,
                    'registered_by' => $user->id,
                ]),
            };

            return response()->json([
                'success' => true,
                'message' => 'Transizione eseguita con successo',
                'data' => $result->load([
                    'user:id,name,surname',
                    'creator:id,name,surname',
                    'contact',
                    'sumupConfig',
                    'gmailAccount',
                    'emailTemplate',
                    'service:id,pickup_datetime,pickup_location,status_id',
                    'stateTransitions' => fn($q) => $q->with('actor:id,name,surname')->orderBy('created_at', 'desc'),
                    'items',
                ]),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get state transition history for a quote.
     */
    public function getTransitions(Quote $quote)
    {
        $transitions = $quote->stateTransitions()
            ->with('actor:id,name,surname')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $transitions]);
    }

    /**
     * Preview rendered email for a quote.
     */
    public function previewEmail(Request $request, Quote $quote)
    {
        $request->validate([
            'email_template_id' => 'required|integer|exists:quote_email_templates,id',
        ]);

        $template = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('id', $request->email_template_id)
            ->where('company_id', $quote->company_id)
            ->firstOrFail();

        $templateService = new QuoteTemplateService();
        $rendered = $templateService->render($template, $quote);

        return response()->json(['data' => $rendered]);
    }

    /**
     * Create a new version of a quote.
     */
    public function createVersion(Quote $quote)
    {
        if (!$quote->is_active_version) {
            return response()->json([
                'success' => false,
                'message' => 'Solo la versione attiva può generare una nuova versione',
            ], 422);
        }

        $user = Auth::user();

        $newQuote = DB::transaction(function () use ($quote, $user) {
            // Archive current version
            $this->archiveVersion($quote, $user);

            // Create new version from current
            return $this->duplicateAsNewVersion($quote, $user);
        });

        return response()->json([
            'success' => true,
            'message' => 'Nuova versione creata con successo',
            'data' => $newQuote->load(['user:id,name,surname', 'creator:id,name,surname', 'contact', 'items']),
        ], 201);
    }

    /**
     * Get all versions of a quote group.
     */
    public function getVersions(Quote $quote)
    {
        $versions = Quote::withoutGlobalScopes()
            ->where('company_id', $quote->company_id)
            ->where('quote_group_id', $quote->quote_group_id)
            ->withCount('items')
            ->orderBy('version', 'desc')
            ->get([
                'id', 'quote_group_id', 'version', 'status', 'is_active_version',
                'client_name', 'final_price_rounded', 'deposit_total',
                'created_at', 'archived_at',
            ]);

        return response()->json(['data' => $versions]);
    }

    /**
     * Restore an archived version (creates a new version with the archived data).
     */
    public function restoreVersion(Quote $quote)
    {
        if ($quote->is_active_version) {
            return response()->json([
                'success' => false,
                'message' => 'Questa versione è già attiva',
            ], 422);
        }

        $user = Auth::user();

        $newQuote = DB::transaction(function () use ($quote, $user) {
            // Find and archive the currently active version
            $activeVersion = Quote::withoutGlobalScopes()
                ->where('company_id', $quote->company_id)
                ->where('quote_group_id', $quote->quote_group_id)
                ->where('is_active_version', true)
                ->first();

            if ($activeVersion) {
                $this->archiveVersion($activeVersion, $user);
            }

            // Create new version from the archived one
            return $this->duplicateAsNewVersion($quote, $user);
        });

        return response()->json([
            'success' => true,
            'message' => 'Versione ripristinata con successo',
            'data' => $newQuote->load(['user:id,name,surname', 'creator:id,name,surname', 'contact', 'items']),
        ], 201);
    }

    /**
     * Archive a version, deactivating SumUp/Gmail if needed.
     */
    private function archiveVersion(Quote $quote, $user): void
    {
        // If the quote has an active offer (approved/sent), revert it first
        if (in_array($quote->status, [Quote::STATUS_APPROVED, Quote::STATUS_SENT])) {
            $stateMachine = new QuoteStateMachineService();
            $stateMachine->revertToDraft($quote, $user);
            $quote->refresh();
        }

        $quote->update([
            'is_active_version' => false,
            'archived_at' => now(),
        ]);
    }

    /**
     * Duplicate a quote as a new version in the same group.
     */
    private function duplicateAsNewVersion(Quote $source, $user): Quote
    {
        $fieldsToCopy = [
            'contact_id', 'client_name', 'client_email', 'service_date', 'notes',
            'vat_percentage', 'card_fees_percentage',
            'override_taxable', 'discount_percentage', 'discount_name',
            'deposit_percentage', 'client_price', 'reference_url',
        ];

        $data = [];
        foreach ($fieldsToCopy as $field) {
            $data[$field] = $source->$field;
        }

        // Load items from source
        $source->load('items');
        $itemsData = $source->items->map(function ($item) {
            return $item->only([
                'pricing_destination_id', 'destination_name', 'service_type',
                'mileage', 'extra_km', 'duration_hours', 'extension_hours',
                'extra_travel_hours', 'toll_cost', 'pax_count',
                'experience_per_pax', 'taxable_price', 'sort_order', 'service_date',
            ]);
        })->toArray();

        // Calculate totals
        $itemsTaxableSum = collect($itemsData)->sum('taxable_price');
        $calculated = PricingCalculatorService::calculateQuoteTotals(array_merge($data, [
            'items_taxable_sum' => $itemsTaxableSum,
        ]));

        // Get next version number
        $maxVersion = Quote::withoutGlobalScopes()
            ->where('quote_group_id', $source->quote_group_id)
            ->max('version');

        $newQuote = Quote::create(array_merge($data, $calculated, [
            'company_id' => $source->company_id,
            'quote_group_id' => $source->quote_group_id,
            'version' => $maxVersion + 1,
            'is_active_version' => true,
            'status' => Quote::STATUS_DRAFT,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]));

        foreach ($itemsData as $item) {
            $newQuote->items()->create($item);
        }

        return $newQuote;
    }

    /**
     * Create a Quote from a Richiesta, pre-populating items from righe_richiesta.
     */
    public function createFromRichiesta(Request $request, string $richiestaId)
    {
        $richiesta = Richiesta::with(['contact', 'righe'])->findOrFail($richiestaId);
        $user = Auth::user();

        $quoteGroupId = 0;
        $version = 1;

        // If archive_current is set, archive the existing active quote and create new version in same group
        if ($request->boolean('archive_current')) {
            $existingQuote = Quote::withoutGlobalScopes()
                ->where('richiesta_id', $richiesta->id)
                ->where('is_active_version', true)
                ->first();

            if ($existingQuote) {
                // Cleanup SumUp checkout (deactivate so old link stops working)
                if ($existingQuote->sumup_checkout_id && $existingQuote->sumupConfig) {
                    try {
                        $sumupService = new \App\Services\SumUpService($existingQuote->sumupConfig);
                        $sumupService->deactivateCheckout($existingQuote->sumup_checkout_id);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::channel('gmail')->warning(
                            "Failed to deactivate SumUp checkout on archive: " . $e->getMessage()
                        );
                    }
                }

                // Cleanup Gmail draft (delete if exists)
                if ($existingQuote->gmail_draft_id && $existingQuote->gmailAccount) {
                    try {
                        $gmailService = new \App\Services\GmailService($existingQuote->gmailAccount);
                        $gmailService->deleteDraft($existingQuote->gmail_draft_id);
                    } catch (\Exception $e) {
                        // Draft may already be sent — ignore
                    }
                }

                // Archive without changing status
                $existingQuote->update([
                    'is_active_version' => false,
                    'archived_at' => now(),
                ]);

                $quoteGroupId = $existingQuote->quote_group_id;
                $version = Quote::withoutGlobalScopes()
                    ->where('quote_group_id', $quoteGroupId)
                    ->max('version') + 1;
            }
        }

        // Min date from righe
        $minDate = $richiesta->righe->whereNotNull('data_servizio')->min('data_servizio');

        $quote = Quote::create([
            'company_id' => $richiesta->company_id,
            'richiesta_id' => $richiesta->id,
            'user_id' => $user->id,
            'status' => Quote::STATUS_DRAFT,
            'contact_id' => $richiesta->contact_id,
            'client_name' => $richiesta->contact->name ?? '',
            'client_email' => $richiesta->contact->email ?? '',
            'service_date' => $minDate,
            'quote_group_id' => $quoteGroupId,
            'version' => $version,
            'is_active_version' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Set quote_group_id = quote id if new group
        if ($quoteGroupId === 0) {
            $quote->update(['quote_group_id' => $quote->id]);
        }

        // Create quote items from righe_richiesta
        foreach ($richiesta->righe as $index => $riga) {
            QuoteItem::create([
                'quote_id' => $quote->id,
                'riga_richiesta_id' => $riga->id,
                'destination_name' => $riga->dropoff,
                'service_type' => $riga->tipo_servizio,
                'pax_count' => $riga->passeggeri ?? 0,
                'sort_order' => $index,
                'service_date' => $riga->data_servizio,
            ]);
        }

        // Transition richiesta to preventivata if in_lavorazione
        if (in_array($richiesta->stato, [Richiesta::STATO_NUOVA, Richiesta::STATO_IN_LAVORAZIONE])) {
            $richiestaStateMachine = new RichiestaStateMachineService();
            try {
                if ($richiesta->stato === Richiesta::STATO_NUOVA) {
                    $richiestaStateMachine->transition($richiesta, Richiesta::STATO_IN_LAVORAZIONE);
                    $richiesta->refresh();
                }
                $richiestaStateMachine->transition($richiesta, Richiesta::STATO_PREVENTIVATA);
            } catch (\Exception $e) {
                // Don't fail quote creation if richiesta transition fails
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Preventivo creato dalla richiesta',
            'data' => $quote->fresh()->load(['user:id,name,surname', 'creator:id,name,surname', 'contact', 'items', 'richiesta']),
        ], 201);
    }

    private function validateQuote(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'service_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'reference_url' => 'nullable|string|max:2048',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'card_fees_percentage' => 'nullable|numeric|min:0|max:100',
            'override_taxable' => 'nullable|numeric|min:0',
            'client_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_name' => 'nullable|string|max:255',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
    }

    private function validateItems(Request $request): array
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.destination_name' => 'nullable|string|max:255',
            'items.*.service_type' => 'nullable|string|max:50',
            'items.*.pricing_destination_id' => 'nullable|integer|exists:pricing_destinations,id',
            'items.*.mileage' => 'nullable|numeric|min:0',
            'items.*.extra_km' => 'nullable|numeric|min:0',
            'items.*.duration_hours' => 'nullable|numeric|min:0',
            'items.*.extension_hours' => 'nullable|numeric|min:0',
            'items.*.extra_travel_hours' => 'nullable|numeric|min:0',
            'items.*.toll_cost' => 'nullable|numeric|min:0',
            'items.*.pax_count' => 'nullable|integer|min:0',
            'items.*.experience_per_pax' => 'nullable|numeric|min:0',
            'items.*.taxable_price' => 'nullable|numeric|min:0',
            'items.*.service_date' => 'nullable|date',
        ]);

        return collect($request->input('items', []))->map(function ($item) {
            return [
                'pricing_destination_id' => $item['pricing_destination_id'] ?? null,
                'destination_name' => $item['destination_name'] ?? null,
                'service_type' => $item['service_type'] ?? null,
                'mileage' => $item['mileage'] ?? 0,
                'extra_km' => $item['extra_km'] ?? 0,
                'duration_hours' => $item['duration_hours'] ?? 0,
                'extension_hours' => $item['extension_hours'] ?? 0,
                'extra_travel_hours' => $item['extra_travel_hours'] ?? 0,
                'toll_cost' => $item['toll_cost'] ?? 0,
                'pax_count' => $item['pax_count'] ?? 0,
                'experience_per_pax' => $item['experience_per_pax'] ?? 0,
                'taxable_price' => $item['taxable_price'] ?? 0,
                'service_date' => $item['service_date'] ?? null,
            ];
        })->toArray();
    }
}
