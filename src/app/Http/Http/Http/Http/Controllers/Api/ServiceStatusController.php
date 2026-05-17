<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServiceStatus::with('company');

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', "%{$search}%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $statuses = $query->paginate($perPage);

        return response()->json($statuses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'color_code' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $status = ServiceStatus::create($validated);

        return response()->json($status->load('company'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceStatus $serviceStatus): JsonResponse
    {
        return response()->json($serviceStatus->load('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceStatus $serviceStatus): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'color_code' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $serviceStatus->update($validated);

        return response()->json($serviceStatus->load('company'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceStatus $serviceStatus): JsonResponse
    {
        $serviceStatus->delete();

        return response()->json(['message' => 'Service status deleted successfully'], 200);
    }
}
