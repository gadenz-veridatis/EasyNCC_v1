<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteStateTransition;
use App\Services\PricingCalculatorService;
use App\Services\QuoteStateMachineService;
use App\Services\QuoteTemplateService;
use App\Models\QuoteEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Quote::query();

        // Multi-tenancy for super-admin
        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $query->withoutGlobalScopes()->where('company_id', $request->company_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'ilike', "%{$search}%")
                  ->orWhere('destination_name', 'ilike', "%{$search}%")
                  ->orWhere('service_type', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
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
        $quotes = $query->with(['user:id,name,surname', 'creator:id,name,surname'])
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

        // Get pricing config and recalculate server-side
        $config = PricingCalculatorService::getConfig($companyId);
        $calculated = PricingCalculatorService::calculate($validated, $config);

        $quote = Quote::create(array_merge($validated, $calculated, [
            'company_id' => $companyId,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Preventivo creato con successo',
            'data' => $quote->load(['user:id,name,surname', 'creator:id,name,surname']),
        ], 201);
    }

    public function show(Quote $quote)
    {
        return response()->json([
            'data' => $quote->load([
                'user:id,name,surname',
                'creator:id,name,surname',
                'updater:id,name,surname',
                'sumupConfig',
                'gmailAccount',
                'emailTemplate',
                'service:id,pickup_datetime,pickup_location,status_id',
                'stateTransitions' => fn($q) => $q->with('actor:id,name,surname')->orderBy('created_at', 'desc'),
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
        $companyId = $quote->company_id;

        // Recalculate server-side
        $config = PricingCalculatorService::getConfig($companyId);
        $inputsForCalc = array_merge($quote->toArray(), $validated);
        $calculated = PricingCalculatorService::calculate($inputsForCalc, $config);

        $quote->update(array_merge($validated, $calculated, [
            'updated_by' => $user->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Preventivo aggiornato con successo',
            'data' => $quote->fresh()->load(['user:id,name,surname', 'creator:id,name,surname']),
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

        // Fields to copy (base pricing/service data)
        $fieldsToCopy = [
            'client_name', 'client_email', 'service_date', 'notes',
            'destination_name', 'service_type', 'mileage', 'extra_km',
            'duration_hours', 'extension_hours', 'extra_travel_hours',
            'toll_cost', 'pax_count', 'experience_per_pax', 'seasonality',
            'vehicle_fill', 'vat_percentage', 'card_fees_percentage',
            'surcharge_percentage', 'travel_costs', 'override_taxable',
            'discount_percentage', 'discount_name', 'deposit_percentage',
            'client_price', 'reference_url',
        ];

        $data = [];
        foreach ($fieldsToCopy as $field) {
            $data[$field] = $quote->$field;
        }

        // Recalculate pricing
        $config = PricingCalculatorService::getConfig($quote->company_id);
        $calculated = PricingCalculatorService::calculate($data, $config);

        $newQuote = Quote::create(array_merge($data, $calculated, [
            'company_id' => $quote->company_id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'status' => Quote::STATUS_DRAFT,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Preventivo duplicato con successo',
            'data' => $newQuote->load(['user:id,name,surname', 'creator:id,name,surname']),
        ], 201);
    }

    public function restore($id)
    {
        $quote = Quote::withTrashed()->findOrFail($id);
        $quote->restore();

        return response()->json([
            'success' => true,
            'message' => 'Preventivo ripristinato con successo',
            'data' => $quote,
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
            'action' => 'required|string|in:approve,send,revert_to_draft',
            'sumup_config_id' => 'nullable|integer',
            'gmail_account_id' => 'nullable|integer',
            'email_template_id' => 'nullable|integer',
            'client_email' => 'nullable|email|max:255',
            'rendered_subject' => 'nullable|string|max:500',
            'rendered_body_html' => 'nullable|string',
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
            };

            return response()->json([
                'success' => true,
                'message' => 'Transizione eseguita con successo',
                'data' => $result->load([
                    'user:id,name,surname',
                    'creator:id,name,surname',
                    'sumupConfig',
                    'gmailAccount',
                    'emailTemplate',
                    'service:id,pickup_datetime,pickup_location,status_id',
                    'stateTransitions' => fn($q) => $q->with('actor:id,name,surname')->orderBy('created_at', 'desc'),
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

    private function validateQuote(Request $request, bool $isUpdate = false): array
    {
        $sometimes = $isUpdate ? 'sometimes|' : '';

        return $request->validate([
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'service_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'destination_name' => 'nullable|string|max:255',
            'service_type' => 'nullable|string|max:50',
            'mileage' => 'nullable|numeric|min:0',
            'extra_km' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|numeric|min:0',
            'extension_hours' => 'nullable|numeric|min:0',
            'extra_travel_hours' => 'nullable|numeric|min:0',
            'toll_cost' => 'nullable|numeric|min:0',
            'pax_count' => 'nullable|integer|min:0',
            'experience_per_pax' => 'nullable|numeric|min:0',
            'seasonality' => 'nullable|string|in:low,average,high',
            'vehicle_fill' => 'nullable|string|in:car,van,full',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'card_fees_percentage' => 'nullable|numeric|min:0|max:100',
            'surcharge_percentage' => 'nullable|numeric|min:0|max:100',
            'travel_costs' => 'nullable|numeric|min:0',
            'override_taxable' => 'nullable|numeric|min:0',
            'client_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_name' => 'nullable|string|max:255',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
    }
}
