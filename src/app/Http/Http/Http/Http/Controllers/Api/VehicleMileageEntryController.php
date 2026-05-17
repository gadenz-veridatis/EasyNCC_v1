<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleMileageEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleMileageEntryController extends Controller
{
    /**
     * Display a paginated listing of mileage entries for a vehicle.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $entries = $vehicle->mileageEntries()
            ->with('creator:id,name,surname')
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($entries);
    }

    /**
     * Store a new mileage entry.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'mileage' => 'required|integer|min:0',
            'entry_date' => 'required|date',
            'update_type' => 'required|string|in:manual,correction,service',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        $entry = $vehicle->mileageEntries()->create($validated);
        $entry->load('creator:id,name,surname');

        return response()->json($entry, 201);
    }

    /**
     * Update the specified mileage entry.
     */
    public function update(Request $request, Vehicle $vehicle, VehicleMileageEntry $entry): JsonResponse
    {
        if ($entry->vehicle_id !== $vehicle->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'mileage' => 'sometimes|integer|min:0',
            'entry_date' => 'sometimes|date',
            'update_type' => 'sometimes|string|in:manual,correction,service',
            'notes' => 'nullable|string',
        ]);

        $entry->update($validated);
        $entry->load('creator:id,name,surname');

        return response()->json($entry);
    }

    /**
     * Remove the specified mileage entry.
     */
    public function destroy(Vehicle $vehicle, VehicleMileageEntry $entry): JsonResponse
    {
        if ($entry->vehicle_id !== $vehicle->id) {
            abort(403, 'Unauthorized');
        }

        $entry->delete();

        return response()->json(['message' => 'Registrazione chilometraggio eliminata'], 200);
    }
}
