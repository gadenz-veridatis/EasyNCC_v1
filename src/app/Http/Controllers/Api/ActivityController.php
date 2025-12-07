<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with([
            'activityType',
            'supplier',
            'service:id,reference_number,client_id',
            'service.client:id,name,surname,email',
            'company:id,name'
        ]);

        // Multi-tenancy: Filter by company
        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        // Filter by service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by activity type
        if ($request->filled('activity_type_id')) {
            $query->where('activity_type_id', $request->activity_type_id);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by payment type
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Search on name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('end_time', '<=', $request->end_date);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'start_time');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $activities = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'activity_type_id' => 'nullable|exists:activity_types,id',
            'name' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'cost' => 'nullable|numeric|min:0',
            'cost_per_person' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|in:INCLUSO,CLIENTE,AGENZIA,NESSUNO',
            'notes' => 'nullable|string',
        ]);

        // Set company_id based on user role
        if ($request->user()->isSuperAdmin() && $request->filled('company_id')) {
            $validated['company_id'] = $request->company_id;
        } else {
            $validated['company_id'] = $request->user()->company_id;
        }

        $activity = Activity::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity created successfully',
            'data' => $activity->load(['activityType', 'supplier', 'service', 'company']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $activity->load(['activityType', 'supplier', 'service', 'company']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'sometimes|nullable|exists:services,id',
            'activity_type_id' => 'sometimes|nullable|exists:activity_types,id',
            'name' => 'sometimes|required|string|max:255',
            'supplier_id' => 'sometimes|nullable|exists:users,id',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'cost' => 'sometimes|nullable|numeric|min:0',
            'cost_per_person' => 'sometimes|nullable|numeric|min:0',
            'payment_type' => 'sometimes|nullable|in:INCLUSO,CLIENTE,AGENZIA,NESSUNO',
            'notes' => 'nullable|string',
        ]);

        $activity->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully',
            'data' => $activity->load(['activityType', 'supplier', 'service', 'company']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity): JsonResponse
    {
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity deleted successfully',
        ], 200);
    }
}
