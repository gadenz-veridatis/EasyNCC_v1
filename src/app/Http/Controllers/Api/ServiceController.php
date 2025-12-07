<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // For list view, only load essential relationships with minimal fields
        // This significantly reduces query time and memory usage
        $query = Service::with([
            'vehicle:id,license_plate,brand,model',
            'client:id,name,surname,email',
            'status:id,name',
            'company:id,name'
        ]);

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

        // Filter by status
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        // Filter by vehicle
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Filter by client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by driver (many-to-many)
        if ($request->has('driver_id')) {
            $query->whereHas('drivers', function($q) {
                $q->where('service_driver.user_id', request('driver_id'));
            });
        }

        // Filter by date range
        if ($request->filled('pickup_date_from') && $request->filled('pickup_date_to')) {
            $query->whereBetween('pickup_datetime', [
                $request->pickup_date_from . ' 00:00:00',
                $request->pickup_date_to . ' 23:59:59'
            ]);
        } elseif ($request->filled('pickup_date_from')) {
            $query->where('pickup_datetime', '>=', $request->pickup_date_from . ' 00:00:00');
        } elseif ($request->filled('pickup_date_to')) {
            $query->where('pickup_datetime', '<=', $request->pickup_date_to . ' 23:59:59');
        }

        // Search
        if ($request->has('search')) {
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
        // Validate temporal conflicts
        $this->checkConflicts($request);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'reference_number' => 'nullable|string|max:255',
            'service_type' => 'required|string|max:255',
            'passenger_count' => 'required|integer|min:1',
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
            // Baggage
            'dress_code_id' => 'nullable|exists:dress_codes,id',
            'large_luggage' => 'nullable|integer|min:0',
            'medium_luggage' => 'nullable|integer|min:0',
            'small_luggage' => 'nullable|integer|min:0',
            'baby_seat_infant' => 'nullable|integer|min:0',
            'baby_seat_standard' => 'nullable|integer|min:0',
            'baby_seat_booster' => 'nullable|integer|min:0',
            // Service plan
            'pickup_datetime' => 'required|date|after:now',
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
            'deposit_amount' => 'nullable|numeric|min:0',
            'balance_taxable' => 'nullable|numeric|min:0',
            'balance_handling_fees' => 'nullable|numeric|min:0',
            'balance_card_fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Extract passengers and driver_ids
        $passengers = $validated['passengers'];
        $driverIds = $validated['driver_ids'];
        unset($validated['passengers'], $validated['driver_ids']);

        // Create service
        $service = Service::create($validated);

        // Attach drivers (many-to-many)
        $service->drivers()->attach($driverIds);

        // Create passengers (one-to-many)
        foreach ($passengers as $passengerData) {
            $service->passengers()->create($passengerData);
        }

        return response()->json($service->load([
            'vehicle',
            'client',
            'intermediary',
            'supplier',
            'dressCode',
            'status',
            'drivers.driverProfile',
            'passengers',
            'stops',
            'payments',
            'costs',
            'activities.activityType',
            'activities.supplier',
            'accountingTransactions',
            'company'
        ]), 201);
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
            'drivers.driverProfile',
            'passengers',
            'stops',
            'payments',
            'costs',
            'activities.activityType',
            'activities.supplier',
            'accountingTransactions',
            'company'
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service): JsonResponse
    {
        // Validate temporal conflicts if pickup/dropoff dates are being updated
        if ($request->has('pickup_datetime') || $request->has('dropoff_datetime')) {
            $this->checkConflicts($request, $service->id);
        }

        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'reference_number' => 'sometimes|string|max:255',
            'service_type' => 'sometimes|string|max:255',
            'passenger_count' => 'sometimes|integer|min:1',
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
            'deposit_amount' => 'nullable|numeric|min:0',
            'balance_taxable' => 'nullable|numeric|min:0',
            'balance_handling_fees' => 'nullable|numeric|min:0',
            'balance_card_fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Handle passengers update
        if (isset($validated['passengers'])) {
            $passengers = $validated['passengers'];
            unset($validated['passengers']);

            // Delete existing passengers and create new ones
            $service->passengers()->delete();
            foreach ($passengers as $passengerData) {
                // Remove id if present (not needed for creation)
                unset($passengerData['id']);
                $service->passengers()->create($passengerData);
            }
        }

        // Handle driver_ids update
        if (isset($validated['driver_ids'])) {
            $driverIds = $validated['driver_ids'];
            unset($validated['driver_ids']);

            // Sync drivers (many-to-many)
            $service->drivers()->sync($driverIds);
        }

        // Update service
        $service->update($validated);

        return response()->json($service->load([
            'vehicle',
            'client',
            'intermediary',
            'supplier',
            'dressCode',
            'status',
            'drivers.driverProfile',
            'passengers',
            'stops',
            'payments',
            'costs',
            'activities.activityType',
            'activities.supplier',
            'accountingTransactions',
            'company'
        ]));
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
