<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with([
            'assignedUsers:id,name,surname,email,role',
            'company:id,name',
            'service:id,reference_number'
        ]);

        // Multi-tenancy: Filter by company
        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        // Filter by assigned user (driver and accountant see only their tasks)
        $userRole = $request->user()->role;
        if (in_array($userRole, ['driver', 'accountant'])) {
            $query->whereHas('assignedUsers', function($q) use ($request) {
                $q->where('users.id', $request->user()->id);
            });
        } elseif ($request->filled('assigned_to')) {
            $query->whereHas('assignedUsers', function($q) use ($request) {
                $q->where('users.id', $request->assigned_to);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search on name and notes
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('due_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('due_date', '<=', $request->end_date);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'due_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $tasks = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'due_date' => 'nullable|date',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
            'notes' => 'nullable|string',
            'status' => 'required|in:to_complete,completed,cancelled',
        ]);

        // Set company_id based on user role
        if ($request->user()->isSuperAdmin() && $request->filled('company_id')) {
            $validated['company_id'] = $request->company_id;
        } else {
            $validated['company_id'] = $request->user()->company_id;
        }

        // Extract assigned_users before creating task
        $assignedUsers = $validated['assigned_users'] ?? [];
        unset($validated['assigned_users']);

        $task = Task::create($validated);

        // Attach assigned users
        if (!empty($assignedUsers)) {
            $task->assignedUsers()->attach($assignedUsers);
        }

        $task->load(['assignedUsers', 'company', 'service']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        // Check access
        if (!$request->user()->isSuperAdmin() && $task->company_id !== $request->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Driver and accountant can only see their own tasks
        $userRole = $request->user()->role;
        if (in_array($userRole, ['driver', 'accountant'])) {
            $isAssigned = $task->assignedUsers()->where('users.id', $request->user()->id)->exists();
            if (!$isAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        $task->load(['assignedUsers', 'company', 'service']);

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        // Check access
        if (!$request->user()->isSuperAdmin() && $task->company_id !== $request->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $userRole = $request->user()->role;

        // Driver and accountant can only update their own tasks and only status/notes
        if (in_array($userRole, ['driver', 'accountant'])) {
            $isAssigned = $task->assignedUsers()->where('users.id', $request->user()->id)->exists();
            if (!$isAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $validated = $request->validate([
                'status' => 'sometimes|required|in:to_complete,completed,cancelled',
                'notes' => 'nullable|string',
            ]);
        } else {
            // Admin, operator, super-admin can update all fields
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'service_id' => 'nullable|exists:services,id',
                'due_date' => 'nullable|date',
                'assigned_users' => 'nullable|array',
                'assigned_users.*' => 'exists:users,id',
                'notes' => 'nullable|string',
                'status' => 'sometimes|required|in:to_complete,completed,cancelled',
            ]);
        }

        // Extract assigned_users before updating task
        $assignedUsers = $validated['assigned_users'] ?? null;
        unset($validated['assigned_users']);

        $task->update($validated);

        // Sync assigned users if provided
        if ($assignedUsers !== null) {
            $task->assignedUsers()->sync($assignedUsers);
        }

        $task->load(['assignedUsers', 'company', 'service']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        // Check access
        if (!$request->user()->isSuperAdmin() && $task->company_id !== $request->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ], 200);
    }
}
