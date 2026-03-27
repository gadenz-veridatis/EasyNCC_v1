<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\ActivityType;
use App\Models\DressCode;
use App\Models\PaymentType;
use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\ServiceType;
use App\Models\Settings;
use App\Models\TransactionStatus;
use App\Services\ServiceOverlapService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    protected ServiceOverlapService $overlapService;

    public function __construct(ServiceOverlapService $overlapService)
    {
        $this->overlapService = $overlapService;
    }

    /**
     * Check for overlaps before saving a service.
     * Returns overlap information for user confirmation.
     */
    public function checkOverlaps(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_departure_datetime' => 'required|date',
            'vehicle_return_datetime' => 'required|date',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_ids' => 'nullable|array',
            'driver_ids.*' => 'exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'exclude_service_id' => 'nullable|exists:services,id',
        ]);

        $serviceData = [
            'vehicle_departure_datetime' => $validated['vehicle_departure_datetime'],
            'vehicle_return_datetime' => $validated['vehicle_return_datetime'],
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'driver_ids' => $validated['driver_ids'] ?? [],
            'company_id' => $validated['company_id'],
        ];

        $overlaps = $this->overlapService->checkOverlaps(
            $serviceData,
            $validated['exclude_service_id'] ?? null
        );

        return response()->json([
            'has_overlaps' => count($overlaps) > 0,
            'overlaps' => $overlaps,
        ]);
    }

    /**
     * Get all form initialization data in a single request.
     * Combines dictionaries + settings to reduce API calls on form load.
     */
    public function formData(Request $request): JsonResponse
    {
        $companyId = $request->user()->isSuperAdmin()
            ? $request->input('company_id')
            : $request->user()->company_id;

        if (!$companyId) {
            return response()->json(['error' => 'company_id required'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'dress_codes' => DressCode::where('company_id', $companyId)->orderBy('name')->get(),
                'service_statuses' => ServiceStatus::where('company_id', $companyId)->orderBy('name')->get(),
                'service_types' => ServiceType::where('company_id', $companyId)->orderBy('name')->get(),
                'activity_types' => ActivityType::where('company_id', $companyId)->orderBy('name')->get(),
                'accounting_entries' => AccountingEntry::where('company_id', $companyId)->orderBy('name')->get(),
                'payment_types' => PaymentType::where('company_id', $companyId)->orderBy('name')->get(),
                'transaction_statuses' => TransactionStatus::where('company_id', $companyId)->where('is_active', true)->orderBy('sort_order')->get(),
                'settings' => Settings::where('company_id', $companyId)->with('defaultSupplier:id,name,surname,email')->first(),
            ],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // For list view, load essential relationships for display
        // Overlap details, transactions, tasks loaded on-demand via show()
        $query = Service::with([
            'vehicle:id,license_plate,brand,model',
            'client:id,name,surname,email,phone',
            'intermediary:id,name,surname,email,phone',
            'supplier:id,name,surname',
            'status:id,name,color_code,bg_color',
            'company:id,name',
            'drivers' => function ($q) {
                $q->withTrashed()->with('driverProfile:user_id,color');
            },
            'dressCode:id,name',
            'passengers:id,service_id,name,surname,phone,nationality',
            'activities.activityType:id,name',
            'activities.supplier:id,name,surname',
        ]);

        // Add counts for notifications (lightweight counts only)
        if ($request->filled('with_counts') && $request->with_counts) {
            $query->withCount([
                'accountingTransactions',
                'tasks',
                'tasks as incomplete_tasks_count' => function ($query) {
                    $query->where('status', '!=', 'completed');
                },
                'overlaps',
                'overlappedBy'
            ]);
        }

        // Multi-tenancy: Filter by company
        // Super-admin can see all companies or filter by company_id
        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            // If no company_id specified, show all services
        } else {
            // Other users see only their company's services
            $query->where('company_id', $request->user()->company_id);
        }

        // Filter by status (by ID or by name)
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        } elseif ($request->filled('status')) {
            $query->whereHas('status', function($q) use ($request) {
                $q->where('name', $request->status);
            });
        }

        // Filter by vehicle
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by intermediary
        if ($request->filled('intermediary_id')) {
            $query->where('intermediary_id', $request->intermediary_id);
        }

        // Filter by service type (stored as string)
        if ($request->filled('service_type_id')) {
            $query->where('service_type', $request->service_type_id);
        }

        // Filter by driver (many-to-many)
        if ($request->filled('driver_id')) {
            $query->whereHas('drivers', function($q) use ($request) {
                $q->where('service_driver.user_id', $request->driver_id);
            });
        }

        // Filter by reference name (contact_name)
        if ($request->filled('reference_name')) {
            $query->where('contact_name', 'ilike', '%' . $request->reference_name . '%');
        }

        // Filter by date range overlap: services whose duration [pickup, dropoff]
        // overlaps even partially with the filter interval [date_from, date_to]
        // Two intervals [A,B] and [C,D] overlap when A <= D AND B >= C
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->where('pickup_datetime', '<=', $request->date_to . ' 23:59:59')
                  ->where('dropoff_datetime', '>=', $request->date_from . ' 00:00:00');
        } elseif ($request->filled('date_from')) {
            // Services not ended before this date
            $query->where('dropoff_datetime', '>=', $request->date_from . ' 00:00:00');
        } elseif ($request->filled('date_to')) {
            // Services that started by this date
            $query->where('pickup_datetime', '<=', $request->date_to . ' 23:59:59');
        }

        // Filter by passenger name
        if ($request->filled('passenger_name')) {
            $passengerName = $request->passenger_name;
            $query->whereHas('passengers', function($q) use ($passengerName) {
                $q->where('name', 'ilike', '%' . $passengerName . '%')
                  ->orWhere('surname', 'ilike', '%' . $passengerName . '%');
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pickup_address', 'ilike', "%{$search}%")
                  ->orWhere('dropoff_address', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'pickup_datetime');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $services = $query->paginate($perPage);

        return response()->json($services);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Check for overlaps (unless user has confirmed them)
        $confirmOverlaps = $request->boolean('confirm_overlaps', false);
        $overlaps = [];

        $serviceData = [
            'vehicle_departure_datetime' => $request->input('vehicle_departure_datetime'),
            'vehicle_return_datetime' => $request->input('vehicle_return_datetime'),
            'pickup_datetime' => $request->input('pickup_datetime'),
            'dropoff_datetime' => $request->input('dropoff_datetime'),
            'vehicle_id' => $request->input('vehicle_id'),
            'driver_ids' => $request->input('driver_ids', []),
            'company_id' => $request->input('company_id'),
        ];

        if (!$confirmOverlaps) {
            $overlaps = $this->overlapService->checkOverlaps($serviceData);

            if (count($overlaps) > 0) {
                return response()->json([
                    'requires_confirmation' => true,
                    'overlaps' => $overlaps,
                    'message' => 'Sono state rilevate sovrapposizioni. Confermare per procedere.',
                ], 422);
            }
        } else {
            // User confirmed, get overlaps to save them after service creation
            $overlaps = $this->overlapService->checkOverlaps($serviceData);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'reference_number' => 'nullable|string|max:255',
            'service_type' => 'required|string|max:255',
            'passenger_count' => 'required|integer|min:0',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            // Passengers array
            'passengers' => 'required|array|min:1',
            'passengers.*.surname' => 'required|string|max:255',
            'passengers.*.name' => 'required|string|max:255',
            'passengers.*.phone' => 'nullable|string|max:255',
            'passengers.*.email' => 'nullable|email|max:255',
            'passengers.*.nationality' => 'nullable|string|max:255',
            'passengers.*.origin' => 'nullable|string|max:255',
            'passengers.*.carrier_reference' => 'nullable|string|max:255',
            // Counterparts
            'client_id' => 'required|exists:users,id',
            'intermediary_id' => 'nullable|exists:users,id',
            'supplier_id' => 'nullable|exists:users,id',
            // Vehicle
            'vehicle_id' => 'required|exists:vehicles,id',
            'vehicle_not_replaceable' => 'boolean',
            // Drivers
            'driver_ids' => 'required|array|min:1',
            'driver_ids.*' => 'exists:users,id',
            'external_driver_name' => 'nullable|string|max:255',
            'external_driver_phone' => 'nullable|string|max:255',
            'driver_not_replaceable' => 'boolean',
            'driver_must_collect' => 'boolean',
            // Baggage
            'dress_code_id' => 'nullable|exists:dress_codes,id',
            'large_luggage' => 'nullable|integer|min:0',
            'medium_luggage' => 'nullable|integer|min:0',
            'small_luggage' => 'nullable|integer|min:0',
            'baby_seat_infant' => 'nullable|integer|min:0',
            'baby_seat_standard' => 'nullable|integer|min:0',
            'baby_seat_booster' => 'nullable|integer|min:0',
            // Service plan
            'pickup_datetime' => 'required|date',
            'pickup_location' => 'required|string|max:255',
            'pickup_address' => 'required|string',
            'pickup_latitude' => 'nullable|string|max:255',
            'pickup_longitude' => 'nullable|string|max:255',
            'vehicle_departure_datetime' => 'required|date',
            'dropoff_datetime' => 'required|date|after:pickup_datetime',
            'dropoff_location' => 'required|string|max:255',
            'dropoff_address' => 'required|string',
            'dropoff_latitude' => 'nullable|string|max:255',
            'dropoff_longitude' => 'nullable|string|max:255',
            'vehicle_return_datetime' => 'required|date',
            // Economics
            'status_id' => 'required|exists:service_statuses,id',
            'service_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'card_fees_percentage' => 'nullable|numeric|min:0|max:100',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
            'deposit_taxable' => 'nullable|numeric|min:0',
            'deposit_handling_fees' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'balance_taxable' => 'nullable|numeric|min:0',
            'balance_handling_fees' => 'nullable|numeric|min:0',
            'balance_card_fees' => 'nullable|numeric|min:0',
            'balance_sale_type' => 'nullable|string|in:balance_taxable,balance_handling_fees,balance_card_fees',
            'driver_compensation' => 'nullable|numeric|min:0',
            'intermediary_commission' => 'nullable|numeric|min:0',
            'expenses' => 'nullable|numeric|min:0',
            'fuel_cost' => 'nullable|numeric|min:0',
            'toll_cost' => 'nullable|numeric|min:0',
            'parking_cost' => 'nullable|numeric|min:0',
            'other_vehicle_costs' => 'nullable|numeric|min:0',
            'colleague_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Extract passengers and driver_ids
        $passengers = $validated['passengers'];
        $driverIds = $validated['driver_ids'];
        unset($validated['passengers'], $validated['driver_ids']);

        // Add audit fields
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        // Create service
        $service = Service::create($validated);

        // Attach drivers (many-to-many)
        $service->drivers()->attach($driverIds);

        // Create passengers (one-to-many)
        foreach ($passengers as $passengerData) {
            $service->passengers()->create($passengerData);
        }

        // Save overlaps if user confirmed them
        if ($confirmOverlaps && count($overlaps) > 0) {
            $this->overlapService->saveOverlaps($service, $overlaps, true);
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $service->id],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service): JsonResponse
    {
        return response()->json($service->load([
            'vehicle',
            'client',
            'intermediary',
            'supplier',
            'dressCode',
            'status',
            'drivers' => function ($query) {
                $query->withTrashed()->with('driverProfile');
            },
            'passengers',
            'stops',
            'payments',
            'costs',
            'activities' => function ($query) {
                $query->orderBy('sort_order', 'asc')
                      ->orderBy('start_time', 'asc')
                      ->with(['activityType', 'supplier']);
            },
            'accountingTransactions',
            'tasks.assignedUsers',
            'company',
            'creator',
            'updater',
            'overlaps.overlappingService:id,reference_number,vehicle_departure_datetime,vehicle_return_datetime',
            'overlaps.vehicle:id,license_plate,brand,model',
            'overlaps.driver:id,name,surname',
            'overlappedBy.service:id,reference_number,vehicle_departure_datetime,vehicle_return_datetime',
            'overlappedBy.vehicle:id,license_plate,brand,model',
            'overlappedBy.driver:id,name,surname'
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service): JsonResponse
    {
        // Check for overlaps if relevant fields are being updated
        $confirmOverlaps = $request->boolean('confirm_overlaps', false);
        $overlaps = [];

        // Check if overlap-relevant fields actually changed (not just present in request)
        $currentDriverIds = $service->drivers()->pluck('users.id')->sort()->values()->toArray();
        $requestDriverIds = collect($request->input('driver_ids', $currentDriverIds))->sort()->values()->toArray();

        $relevantFieldsChanged = (
            ($request->has('vehicle_departure_datetime') && $request->input('vehicle_departure_datetime') !== $service->vehicle_departure_datetime?->format('Y-m-d\TH:i'))
            || ($request->has('vehicle_return_datetime') && $request->input('vehicle_return_datetime') !== $service->vehicle_return_datetime?->format('Y-m-d\TH:i'))
            || ($request->has('pickup_datetime') && $request->input('pickup_datetime') !== $service->pickup_datetime?->format('Y-m-d\TH:i'))
            || ($request->has('dropoff_datetime') && $request->input('dropoff_datetime') !== $service->dropoff_datetime?->format('Y-m-d\TH:i'))
            || ($request->has('vehicle_id') && (int) $request->input('vehicle_id') !== $service->vehicle_id)
            || ($request->has('driver_ids') && $requestDriverIds !== $currentDriverIds)
        );

        if ($relevantFieldsChanged) {
            $serviceData = [
                'vehicle_departure_datetime' => $request->input('vehicle_departure_datetime', $service->vehicle_departure_datetime?->format('Y-m-d H:i:s')),
                'vehicle_return_datetime' => $request->input('vehicle_return_datetime', $service->vehicle_return_datetime?->format('Y-m-d H:i:s')),
                'pickup_datetime' => $request->input('pickup_datetime', $service->pickup_datetime?->format('Y-m-d H:i:s')),
                'dropoff_datetime' => $request->input('dropoff_datetime', $service->dropoff_datetime?->format('Y-m-d H:i:s')),
                'vehicle_id' => $request->input('vehicle_id', $service->vehicle_id),
                'driver_ids' => $request->input('driver_ids', $currentDriverIds),
                'company_id' => $service->company_id,
            ];

            $overlaps = $this->overlapService->checkOverlaps($serviceData, $service->id);

            if (count($overlaps) > 0 && !$confirmOverlaps) {
                return response()->json([
                    'requires_confirmation' => true,
                    'overlaps' => $overlaps,
                    'message' => 'Sono state rilevate sovrapposizioni. Confermare per procedere.',
                ], 422);
            }
        }

        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'reference_number' => 'sometimes|string|max:255',
            'service_type' => 'sometimes|string|max:255',
            'passenger_count' => 'sometimes|integer|min:0',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            // Passengers array
            'passengers' => 'sometimes|array|min:1',
            'passengers.*.id' => 'sometimes|exists:service_passengers,id',
            'passengers.*.surname' => 'required|string|max:255',
            'passengers.*.name' => 'required|string|max:255',
            'passengers.*.phone' => 'nullable|string|max:255',
            'passengers.*.email' => 'nullable|email|max:255',
            'passengers.*.nationality' => 'nullable|string|max:255',
            'passengers.*.origin' => 'nullable|string|max:255',
            'passengers.*.carrier_reference' => 'nullable|string|max:255',
            // Counterparts
            'client_id' => 'sometimes|exists:users,id',
            'intermediary_id' => 'nullable|exists:users,id',
            'supplier_id' => 'nullable|exists:users,id',
            // Vehicle
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'vehicle_not_replaceable' => 'boolean',
            // Drivers
            'driver_ids' => 'sometimes|array|min:1',
            'driver_ids.*' => 'exists:users,id',
            'external_driver_name' => 'nullable|string|max:255',
            'external_driver_phone' => 'nullable|string|max:255',
            'driver_not_replaceable' => 'boolean',
            'driver_must_collect' => 'boolean',
            // Baggage
            'dress_code_id' => 'nullable|exists:dress_codes,id',
            'large_luggage' => 'nullable|integer|min:0',
            'medium_luggage' => 'nullable|integer|min:0',
            'small_luggage' => 'nullable|integer|min:0',
            'baby_seat_infant' => 'nullable|integer|min:0',
            'baby_seat_standard' => 'nullable|integer|min:0',
            'baby_seat_booster' => 'nullable|integer|min:0',
            // Service plan
            'pickup_datetime' => 'sometimes|date',
            'pickup_location' => 'sometimes|string|max:255',
            'pickup_address' => 'sometimes|string',
            'pickup_latitude' => 'nullable|string|max:255',
            'pickup_longitude' => 'nullable|string|max:255',
            'vehicle_departure_datetime' => 'sometimes|date',
            'dropoff_datetime' => 'sometimes|date',
            'dropoff_location' => 'sometimes|string|max:255',
            'dropoff_address' => 'sometimes|string',
            'dropoff_latitude' => 'nullable|string|max:255',
            'dropoff_longitude' => 'nullable|string|max:255',
            'vehicle_return_datetime' => 'sometimes|date',
            // Economics
            'status_id' => 'sometimes|exists:service_statuses,id',
            'service_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'card_fees_percentage' => 'nullable|numeric|min:0|max:100',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
            'deposit_taxable' => 'nullable|numeric|min:0',
            'deposit_handling_fees' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'balance_taxable' => 'nullable|numeric|min:0',
            'balance_handling_fees' => 'nullable|numeric|min:0',
            'balance_card_fees' => 'nullable|numeric|min:0',
            'balance_sale_type' => 'nullable|string|in:balance_taxable,balance_handling_fees,balance_card_fees',
            'driver_compensation' => 'nullable|numeric|min:0',
            'intermediary_commission' => 'nullable|numeric|min:0',
            'expenses' => 'nullable|numeric|min:0',
            'fuel_cost' => 'nullable|numeric|min:0',
            'toll_cost' => 'nullable|numeric|min:0',
            'parking_cost' => 'nullable|numeric|min:0',
            'other_vehicle_costs' => 'nullable|numeric|min:0',
            'colleague_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Handle passengers update (upsert: update existing, create new, delete removed)
        if (isset($validated['passengers'])) {
            $passengers = $validated['passengers'];
            unset($validated['passengers']);

            $existingIds = [];
            foreach ($passengers as $passengerData) {
                if (!empty($passengerData['id'])) {
                    // Update existing passenger
                    $passengerId = $passengerData['id'];
                    unset($passengerData['id']);
                    $service->passengers()->where('id', $passengerId)->update($passengerData);
                    $existingIds[] = $passengerId;
                } else {
                    // Create new passenger
                    unset($passengerData['id']);
                    $newPassenger = $service->passengers()->create($passengerData);
                    $existingIds[] = $newPassenger->id;
                }
            }
            // Delete passengers that were removed by the user
            $service->passengers()->whereNotIn('id', $existingIds)->delete();
        }

        // Handle driver_ids update
        if (isset($validated['driver_ids'])) {
            $driverIds = $validated['driver_ids'];
            unset($validated['driver_ids']);

            // Sync drivers (many-to-many)
            $service->drivers()->sync($driverIds);
        }

        // Add audit field
        $validated['updated_by'] = $request->user()->id;

        // Update service
        $service->update($validated);

        // Save overlaps (single call - already checked above, no recalculate needed)
        if ($relevantFieldsChanged) {
            $this->overlapService->saveOverlaps($service, $overlaps, $confirmOverlaps);
        }

        // Load popup-relevant relationships for response
        $service->load([
            'vehicle:id,license_plate,brand,model',
            'client:id,name,surname',
            'drivers' => function ($query) {
                $query->withTrashed()->with('driverProfile');
            },
            'dressCode',
            'status',
            'passengers',
            'overlaps.overlappingService:id,reference_number,vehicle_departure_datetime,vehicle_return_datetime',
            'overlaps.vehicle:id,license_plate,brand,model',
            'overlaps.driver:id,name,surname',
            'overlappedBy.service:id,reference_number,vehicle_departure_datetime,vehicle_return_datetime',
            'overlappedBy.vehicle:id,license_plate,brand,model',
            'overlappedBy.driver:id,name,surname'
        ]);

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    /**
     * Lightweight inline update for single-field edits from the service list.
     * Avoids the overhead of full validation and eager-loading.
     */
    public function inlineUpdate(Request $request, Service $service): JsonResponse
    {
        $allowedFields = [
            'status_id' => 'nullable|exists:service_statuses,id',
            'dress_code_id' => 'nullable|exists:dress_codes,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'notes' => 'nullable|string',
        ];

        $fieldsToUpdate = array_intersect_key($request->all(), $allowedFields);

        if (empty($fieldsToUpdate)) {
            return response()->json(['error' => 'No valid fields provided'], 422);
        }

        // Build validation rules only for submitted fields
        $rules = array_intersect_key($allowedFields, $fieldsToUpdate);
        $validated = $request->validate($rules);

        $validated['updated_by'] = $request->user()->id;
        $service->update($validated);

        // Telegram notification is handled centrally by ServiceObserver
        // when status_id changes to the configured trigger status

        // Only load the relation that was updated
        $relations = [];
        if (isset($validated['status_id'])) $relations[] = 'status';
        if (isset($validated['dress_code_id'])) $relations[] = 'dressCode';
        if (isset($validated['vehicle_id'])) $relations[] = 'vehicle:id,license_plate,brand,model';

        if (!empty($relations)) {
            $service->load($relations);
        }

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    /**
     * Duplicate a service with all related data.
     */
    public function duplicate(Request $request, Service $service): JsonResponse
    {
        $service->load(['passengers', 'stops', 'activities', 'drivers']);

        $newData = $service->replicate()->toArray();
        unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['deleted_at']);
        $newData['reference_number'] = 'SRV-' . now()->format('YmdHis');
        $newData['created_by'] = $request->user()->id;
        $newData['updated_by'] = $request->user()->id;
        // Reset status to first available (draft)
        $defaultStatus = \App\Models\ServiceStatus::withoutGlobalScopes()
            ->where('company_id', $service->company_id)
            ->orderBy('id')
            ->first();
        if ($defaultStatus) {
            $newData['status_id'] = $defaultStatus->id;
        }

        $newService = Service::create($newData);

        // Duplicate drivers
        $driverIds = $service->drivers->pluck('id')->toArray();
        if (!empty($driverIds)) {
            $newService->drivers()->attach($driverIds);
        }

        // Duplicate passengers
        foreach ($service->passengers as $passenger) {
            $pData = $passenger->replicate()->toArray();
            unset($pData['id'], $pData['service_id'], $pData['created_at'], $pData['updated_at']);
            $newService->passengers()->create($pData);
        }

        // Duplicate stops
        foreach ($service->stops as $stop) {
            $sData = $stop->replicate()->toArray();
            unset($sData['id'], $sData['service_id'], $sData['created_at'], $sData['updated_at']);
            $newService->stops()->create($sData);
        }

        // Duplicate activities
        foreach ($service->activities as $activity) {
            $aData = $activity->replicate()->toArray();
            unset($aData['id'], $aData['service_id'], $aData['created_at'], $aData['updated_at']);
            $newService->activities()->create($aData);
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $newService->id],
        ], 201);
    }

    /**
     * Create a return service (duplicate with pickup/dropoff swapped).
     */
    public function returnService(Request $request, Service $service): JsonResponse
    {
        $service->load(['passengers', 'stops', 'activities', 'drivers']);

        $newData = $service->replicate()->toArray();
        unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['deleted_at']);
        $newData['reference_number'] = 'SRV-' . now()->format('YmdHis');
        $newData['created_by'] = $request->user()->id;
        $newData['updated_by'] = $request->user()->id;

        // Swap pickup and dropoff
        $newData['pickup_datetime'] = $service->dropoff_datetime;
        $newData['pickup_location'] = $service->dropoff_location;
        $newData['pickup_address'] = $service->dropoff_address;
        $newData['pickup_latitude'] = $service->dropoff_latitude;
        $newData['pickup_longitude'] = $service->dropoff_longitude;
        $newData['dropoff_datetime'] = $service->pickup_datetime;
        $newData['dropoff_location'] = $service->pickup_location;
        $newData['dropoff_address'] = $service->pickup_address;
        $newData['dropoff_latitude'] = $service->pickup_latitude;
        $newData['dropoff_longitude'] = $service->pickup_longitude;
        $newData['vehicle_departure_datetime'] = $service->vehicle_return_datetime;
        $newData['vehicle_return_datetime'] = $service->vehicle_departure_datetime;

        // Reset status
        $defaultStatus = \App\Models\ServiceStatus::withoutGlobalScopes()
            ->where('company_id', $service->company_id)
            ->orderBy('id')
            ->first();
        if ($defaultStatus) {
            $newData['status_id'] = $defaultStatus->id;
        }

        $newService = Service::create($newData);

        // Duplicate drivers
        $driverIds = $service->drivers->pluck('id')->toArray();
        if (!empty($driverIds)) {
            $newService->drivers()->attach($driverIds);
        }

        // Duplicate passengers
        foreach ($service->passengers as $passenger) {
            $pData = $passenger->replicate()->toArray();
            unset($pData['id'], $pData['service_id'], $pData['created_at'], $pData['updated_at']);
            $newService->passengers()->create($pData);
        }

        // Duplicate stops (reversed order)
        $stops = $service->stops->reverse();
        foreach ($stops as $stop) {
            $sData = $stop->replicate()->toArray();
            unset($sData['id'], $sData['service_id'], $sData['created_at'], $sData['updated_at']);
            $newService->stops()->create($sData);
        }

        // Duplicate activities
        foreach ($service->activities as $activity) {
            $aData = $activity->replicate()->toArray();
            unset($aData['id'], $aData['service_id'], $aData['created_at'], $aData['updated_at']);
            $newService->activities()->create($aData);
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $newService->id],
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully'], 200);
    }

    /**
     * Recalculate all overlaps for the current company.
     * Respects current allow_overlapping flags on vehicles and drivers.
     */
    public function recalculateOverlaps(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->role === 'super-admin'
            ? ($request->input('company_id') ?? $user->company_id)
            : $user->company_id;

        // Load all active services for the company
        $services = Service::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereNotNull('vehicle_departure_datetime')
            ->whereNotNull('vehicle_return_datetime')
            ->with('drivers')
            ->get();

        $removedCount = 0;
        $createdCount = 0;

        // Delete all existing overlaps for this company
        $removedCount = \App\Models\ServiceOverlap::whereIn('service_id', $services->pluck('id'))->count();
        \App\Models\ServiceOverlap::whereIn('service_id', $services->pluck('id'))->delete();

        // Recalculate overlaps for each service
        foreach ($services as $service) {
            $serviceData = [
                'vehicle_departure_datetime' => $service->vehicle_departure_datetime?->format('Y-m-d H:i:s'),
                'vehicle_return_datetime' => $service->vehicle_return_datetime?->format('Y-m-d H:i:s'),
                'pickup_datetime' => $service->pickup_datetime?->format('Y-m-d H:i:s'),
                'dropoff_datetime' => $service->dropoff_datetime?->format('Y-m-d H:i:s'),
                'vehicle_id' => $service->vehicle_id,
                'driver_ids' => $service->drivers->pluck('id')->toArray(),
                'company_id' => $companyId,
            ];

            $overlaps = $this->overlapService->checkOverlaps($serviceData, $service->id);

            if (count($overlaps) > 0) {
                $this->overlapService->saveOverlaps($service, $overlaps, true);
                $createdCount += count($overlaps);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Ricalcolo completato: {$removedCount} sovrapposizioni rimosse, {$createdCount} sovrapposizioni trovate.",
            'removed' => $removedCount,
            'created' => $createdCount,
            'services_checked' => $services->count(),
        ]);
    }

    /**
     * Check for temporal conflicts with vehicle or driver assignments
     */
    protected function checkConflicts(Request $request, ?int $excludeServiceId = null): void
    {
        $pickupDateTime = $request->input('pickup_datetime');
        $dropoffDateTime = $request->input('dropoff_datetime');
        $vehicleId = $request->input('vehicle_id');
        $driverIds = $request->input('driver_ids', []);

        if (!$pickupDateTime || !$dropoffDateTime) {
            return;
        }

        // Check vehicle conflicts
        if ($vehicleId) {
            $vehicle = \App\Models\Vehicle::find($vehicleId);

            if ($vehicle && !$vehicle->allow_overlapping) {
                $query = Service::where('vehicle_id', $vehicleId)
                    ->where(function($q) use ($pickupDateTime, $dropoffDateTime) {
                        $q->whereBetween('pickup_datetime', [$pickupDateTime, $dropoffDateTime])
                          ->orWhereBetween('dropoff_datetime', [$pickupDateTime, $dropoffDateTime])
                          ->orWhere(function($q2) use ($pickupDateTime, $dropoffDateTime) {
                              $q2->where('pickup_datetime', '<=', $pickupDateTime)
                                 ->where('dropoff_datetime', '>=', $dropoffDateTime);
                          });
                    });

                if ($excludeServiceId) {
                    $query->where('id', '!=', $excludeServiceId);
                }

                if ($query->exists()) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'Vehicle is not available for the selected time range.'
                    ]);
                }
            }
        }

        // Check driver conflicts
        if (!empty($driverIds)) {
            foreach ($driverIds as $driverId) {
                $driver = \App\Models\User::find($driverId);

                if ($driver && $driver->driverProfile && !$driver->driverProfile->allow_overlapping) {
                    $query = Service::whereHas('drivers', function($q) use ($driverId) {
                        $q->where('service_driver.user_id', $driverId);
                    })->where(function($q) use ($pickupDateTime, $dropoffDateTime) {
                        $q->whereBetween('pickup_datetime', [$pickupDateTime, $dropoffDateTime])
                          ->orWhereBetween('dropoff_datetime', [$pickupDateTime, $dropoffDateTime])
                          ->orWhere(function($q2) use ($pickupDateTime, $dropoffDateTime) {
                              $q2->where('pickup_datetime', '<=', $pickupDateTime)
                                 ->where('dropoff_datetime', '>=', $dropoffDateTime);
                          });
                    });

                    if ($excludeServiceId) {
                        $query->where('id', '!=', $excludeServiceId);
                    }

                    if ($query->exists()) {
                        throw ValidationException::withMessages([
                            'driver_ids' => "Driver {$driverId} is not available for the selected time range."
                        ]);
                    }
                }
            }
        }
    }
}
