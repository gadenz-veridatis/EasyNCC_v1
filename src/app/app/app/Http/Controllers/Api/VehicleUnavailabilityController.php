<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleUnavailability;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleUnavailabilityController extends Controller
{
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
