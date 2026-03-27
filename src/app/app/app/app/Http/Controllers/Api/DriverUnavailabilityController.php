<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverUnavailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DriverUnavailabilityController extends Controller
{
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
