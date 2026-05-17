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
            'supplier:id,name,surname',
            'service:id,reference_number,client_id,pickup_datetime',
            'service.client:id,name,surname',
            'service.drivers:id,name,surname',
            'service.drivers.driverProfile:id,user_id,color',
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

        // Filter by client (via service)
        if ($request->filled('client_id')) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->where('client_id', $request->client_id);
            });
        }

        // Filter by driver (via service)
        if ($request->filled('driver_id')) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->whereHas('drivers', function ($dq) use ($request) {
                    $dq->where('users.id', $request->driver_id);
                });
            });
        }

        // Search on name, notes, and service reference_number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%")
                  ->orWhereHas('service', function ($sq) use ($search) {
                      $sq->where('reference_number', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Sorting
        $allowedSorts = ['start_time', 'name', 'cost', 'payment_type', 'created_at'];
        $sortBy = $request->get('sort_by', 'start_time');
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'start_time';
        }
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
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'cost_per_person' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|string|max:50',
            'should_account' => 'nullable|boolean',
            'confirmation_enabled' => 'nullable|boolean',
            'confirmation_assignee_id' => 'nullable|exists:users,id',
            'accounting_transaction_id' => 'nullable|exists:accounting_transactions,id',
            'notes' => 'nullable|string',
        ]);

        // Set company_id based on user role
        if ($request->user()->isSuperAdmin() && $request->filled('company_id')) {
            $validated['company_id'] = $request->company_id;
        } else {
            $validated['company_id'] = $request->user()->company_id;
        }

        // Auto-assign sort_order: append at end of service's activities
        if (!empty($validated['service_id'])) {
            $maxSortOrder = Activity::where('service_id', $validated['service_id'])
                ->max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;
        }

        $activity = Activity::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity created successfully',
            'data' => $activity->load(['activityType', 'supplier', 'confirmationAssignee:id,name,surname', 'service', 'company']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $activity->load(['activityType', 'supplier', 'confirmationAssignee:id,name,surname', 'service', 'company']),
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
            'start_time' => 'sometimes|nullable|date',
            'end_time' => 'sometimes|nullable|date',
            'cost' => 'sometimes|nullable|numeric|min:0',
            'cost_per_person' => 'sometimes|nullable|numeric|min:0',
            'payment_type' => 'sometimes|nullable|in:INCLUSO,CLIENTE,AGENZIA,NESSUNO',
            'confirmation_enabled' => 'sometimes|nullable|boolean',
            'confirmation_assignee_id' => 'sometimes|nullable|exists:users,id',
            'accounting_transaction_id' => 'sometimes|nullable|exists:accounting_transactions,id',
            'should_account' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $activity->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully',
            'data' => $activity->load(['activityType', 'supplier', 'confirmationAssignee:id,name,surname', 'service', 'company']),
        ]);
    }

    /**
     * Reorder activities for a service.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activities' => 'required|array',
            'activities.*.id' => 'required|integer|exists:activities,id',
            'activities.*.sort_order' => 'required|integer|min:0',
        ]);

        $companyId = $request->user()->isSuperAdmin() && $request->filled('company_id')
            ? $request->company_id
            : $request->user()->company_id;

        foreach ($validated['activities'] as $item) {
            Activity::where('id', $item['id'])
                ->where('company_id', $companyId)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Activities reordered successfully',
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
