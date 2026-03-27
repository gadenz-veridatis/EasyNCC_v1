<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleUnavailability;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleUnavailabilityController extends Controller
{
    /**
     * List all vehicle unavailabilities for the company.
     */
    public function listAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = VehicleUnavailability::with(['vehicle:id,license_plate,brand,model,company_id', 'unavailabilityType:id,name'])
            ->join('vehicles', 'vehicle_unavailabilities.vehicle_id', '=', 'vehicles.id')
            ->select('vehicle_unavailabilities.*');

        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('vehicles.company_id', $request->company_id);
            }
        } else {
            $query->where('vehicles.company_id', $user->company_id);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_unavailabilities.vehicle_id', $request->vehicle_id);
        }

        $unavailabilities = $query->orderBy('vehicle_unavailabilities.start_date', 'desc')->get();

        return response()->json($unavailabilities);
    }

    /**
     * Store a new vehicle unavailability (global endpoint, vehicle_id in body).
     */
    public function storeGlobal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'vehicle_unavailability_type_id' => 'required|exists:vehicle_unavailability_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability = VehicleUnavailability::create($validated);
        $unavailability->load(['vehicle:id,license_plate,brand,model,company_id', 'unavailabilityType:id,name']);

        return response()->json($unavailability, 201);
    }

    public function index(Vehicle $vehicle): JsonResponse
    {
        $unavailabilities = $vehicle->unavailabilities()
            ->with('unavailabilityType:id,name')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($unavailabilities);
    }

    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_unavailability_type_id' => 'required|exists:vehicle_unavailability_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability = $vehicle->unavailabilities()->create($validated);
        $unavailability->load('unavailabilityType:id,name');

        return response()->json($unavailability, 201);
    }

    public function update(Request $request, Vehicle $vehicle, VehicleUnavailability $unavailability): JsonResponse
    {
        if ($unavailability->vehicle_id !== $vehicle->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'vehicle_unavailability_type_id' => 'sometimes|exists:vehicle_unavailability_types,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability->update($validated);
        $unavailability->load('unavailabilityType:id,name');

        return response()->json($unavailability);
    }

    public function destroy(Vehicle $vehicle, VehicleUnavailability $unavailability): JsonResponse
    {
        if ($unavailability->vehicle_id !== $vehicle->id) {
            abort(403, 'Unauthorized');
        }

        $unavailability->delete();

        return response()->json(['message' => 'Unavailability period deleted successfully'], 200);
    }
}
