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
     * Display a listing of unavailability periods for a specific vehicle.
     */
    public function index(Vehicle $vehicle): JsonResponse
    {
        $unavailabilities = $vehicle->unavailabilities()
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($unavailabilities);
    }

    /**
     * Store a newly created unavailability period.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:manutenzione,noleggio,altro',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability = $vehicle->unavailabilities()->create($validated);

        return response()->json($unavailability, 201);
    }

    /**
     * Update the specified unavailability period.
     */
    public function update(Request $request, Vehicle $vehicle, VehicleUnavailability $unavailability): JsonResponse
    {
        // Verify unavailability belongs to vehicle
        if ($unavailability->vehicle_id !== $vehicle->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'type' => 'sometimes|string|in:manutenzione,noleggio,altro',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability->update($validated);

        return response()->json($unavailability);
    }

    /**
     * Remove the specified unavailability period.
     */
    public function destroy(Vehicle $vehicle, VehicleUnavailability $unavailability): JsonResponse
    {
        // Verify unavailability belongs to vehicle
        if ($unavailability->vehicle_id !== $vehicle->id) {
            abort(403, 'Unauthorized');
        }

        $unavailability->delete();

        return response()->json(['message' => 'Unavailability period deleted successfully'], 200);
    }
}
