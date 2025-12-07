<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // For list view, load essential relationships including attachments and unavailabilities
        $query = Vehicle::with(['company:id,name', 'vehicleAttachments', 'unavailabilities']);

        // Multi-tenancy: Filter by company
        // Super-admin can see all companies or filter by company_id
        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            // If no company_id specified, show all vehicles
        } else {
            // Other users see only their company's vehicles
            $query->where('company_id', $request->user()->company_id);
        }

        // Filter by status (only if not empty)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search on license_plate, brand, model (only if not empty)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('license_plate', 'ilike', "%{$search}%")
                  ->orWhere('brand', 'ilike', "%{$search}%")
                  ->orWhere('model', 'ilike', "%{$search}%");
            });
        }

        // Ordinamento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginazione
        $perPage = $request->get('per_page', 15);
        $vehicles = $query->paginate($perPage);

        return response()->json($vehicles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|unique:vehicles,license_plate',
            'brand' => 'required|string',
            'model' => 'required|string',
            'passenger_capacity' => 'required|integer|min:1',
            'purchase_date' => 'nullable|date',
            'ncc_license_number' => 'nullable|string',
            'license_city' => 'nullable|string',
            'allow_overlapping' => 'boolean',
            'status' => 'required|in:in_service,maintenance,out_of_service',
            'notes' => 'nullable|string',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json($vehicle->load('company'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json($vehicle->load(['company', 'assignedDrivers', 'services']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'license_plate' => 'sometimes|string|unique:vehicles,license_plate,' . $vehicle->id,
            'brand' => 'sometimes|string',
            'model' => 'sometimes|string',
            'passenger_capacity' => 'sometimes|integer|min:1',
            'purchase_date' => 'nullable|date',
            'ncc_license_number' => 'nullable|string',
            'license_city' => 'nullable|string',
            'allow_overlapping' => 'boolean',
            'status' => 'sometimes|in:in_service,maintenance,out_of_service',
            'notes' => 'nullable|string',
        ]);

        $vehicle->update($validated);

        return response()->json($vehicle->load('company'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully'], 200);
    }
}
