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
        $query = Service::with([
            'vehicle',
            'client',
            'intermediary',
            'supplier',
            'dressCode',
            'status',
            'drivers',
            'passengers',
            'stops',
            'payments',
            'costs',
            'company'
        ]);

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
        if ($request->has('pickup_date_from') && $request->has('pickup_date_to')) {
            $query->whereBetween('pickup_datetime', [
                $request->pickup_date_from . ' 00:00:00',
                $request->pickup_date_to . ' 23:59:59'
            ]);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pickup_location', 'ilike', "%{$search}%")
                  ->orWhere('dropoff_location', 'ilike', "%{$search}%")
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
            'vehicle_id' => 'required|exists:vehicles,id',
            'client_id' => 'required|exists:users,id',
            'intermediary_id' => 'nullable|exists:users,id',
            'supplier_id' => 'nullable|exists:users,id',
            'dress_code_id' => 'nullable|exists:dress_codes,id',
            'status_id' => 'required|exists:service_statuses,id',
            'pickup_location' => 'required|string',
            'pickup_datetime' => 'required|date_format:Y-m-d H:i:s|after:now',
            'dropoff_location' => 'required|string',
            'dropoff_datetime' => 'required|date_format:Y-m-d H:i:s|after:pickup_datetime',
            'passenger_count' => 'required|integer|min:1',
            'vehicle_not_replaceable' => 'boolean',
            'driver_not_replaceable' => 'boolean',
            'bagagli' => 'nullable|integer|min:0',
            'baby_seat' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'total_amount' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
        ]);

        $service = Service::create($validated);

        return response()->json($service->load([
            'vehicle',
            'client',
            'intermediary',
            'supplier',
            'dressCode',
            'status',
            'drivers',
            'passengers',
            'stops',
            'payments',
            'costs',
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
            'drivers',
            'passengers',
            'stops',
            'payments',
            'costs',
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
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'client_id' => 'sometimes|exists:users,id',
            'intermediary_id' => 'nullable|exists:users,id',
            'supplier_id' => 'nullable|exists:users,id',
            'dress_code_id' => 'nullable|exists:dress_codes,id',
            'status_id' => 'sometimes|exists:service_statuses,id',
            'pickup_location' => 'sometimes|string',
            'pickup_datetime' => 'sometimes|date_format:Y-m-d H:i:s',
            'dropoff_location' => 'sometimes|string',
            'dropoff_datetime' => 'sometimes|date_format:Y-m-d H:i:s',
            'passenger_count' => 'sometimes|integer|min:1',
            'vehicle_not_replaceable' => 'boolean',
            'driver_not_replaceable' => 'boolean',
            'bagagli' => 'nullable|integer|min:0',
            'baby_seat' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'total_amount' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
        ]);

        $service->update($validated);

        return response()->json($service->load([
            'vehicle',
            'client',
            'intermediary',
            'supplier',
            'dressCode',
            'status',
            'drivers',
            'passengers',
            'stops',
            'payments',
            'costs',
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
