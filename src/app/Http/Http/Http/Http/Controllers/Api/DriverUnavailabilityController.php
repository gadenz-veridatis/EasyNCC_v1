<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverUnavailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DriverUnavailabilityController extends Controller
{
    /**
     * List all driver unavailabilities for the company.
     */
    public function listAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = DriverUnavailability::with(['user:id,name,surname,nickname,company_id', 'user.driverProfile:user_id,color', 'leaveType:id,name'])
            ->join('users', 'driver_unavailabilities.user_id', '=', 'users.id')
            ->select('driver_unavailabilities.*');

        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('users.company_id', $request->company_id);
            }
        } else {
            $query->where('users.company_id', $user->company_id);
        }

        if ($request->filled('user_id')) {
            $query->where('driver_unavailabilities.user_id', $request->user_id);
        }

        $unavailabilities = $query->orderBy('driver_unavailabilities.start_date', 'desc')->get();

        return response()->json($unavailabilities);
    }

    /**
     * Store a new driver unavailability (global endpoint, user_id in body).
     */
    public function storeGlobal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability = DriverUnavailability::create($validated);
        $unavailability->load(['user:id,name,surname,nickname,company_id', 'user.driverProfile:user_id,color', 'leaveType:id,name']);

        return response()->json($unavailability, 201);
    }

    public function index(User $user): JsonResponse
    {
        $unavailabilities = $user->unavailabilities()
            ->with('leaveType:id,name')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($unavailabilities);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability = $user->unavailabilities()->create($validated);
        $unavailability->load('leaveType:id,name');

        return response()->json($unavailability, 201);
    }

    public function update(Request $request, User $user, DriverUnavailability $unavailability): JsonResponse
    {
        if ($unavailability->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'leave_type_id' => 'sometimes|exists:leave_types,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $unavailability->update($validated);
        $unavailability->load('leaveType:id,name');

        return response()->json($unavailability);
    }

    public function destroy(User $user, DriverUnavailability $unavailability): JsonResponse
    {
        if ($unavailability->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $unavailability->delete();

        return response()->json(['message' => 'Unavailability period deleted successfully'], 200);
    }
}
