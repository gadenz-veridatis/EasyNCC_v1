<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverUnavailability;
use App\Models\VehicleUnavailability;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnavailabilityCalendarController extends Controller
{
    /**
     * Get all unavailabilities (driver + vehicle) for a date range.
     * Used by the calendar to display background events.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $user = Auth::user();
        $companyId = $user->isSuperAdmin()
            ? $request->input('company_id')
            : $user->company_id;

        $start = $validated['start'];
        $end = $validated['end'];

        // Driver unavailabilities: join with users to filter by company
        $driverQuery = DriverUnavailability::join('users', 'driver_unavailabilities.user_id', '=', 'users.id');
        if ($companyId) {
            $driverQuery->where('users.company_id', $companyId);
        }
        $driverUnavailabilities = $driverQuery
            ->where('driver_unavailabilities.start_date', '<=', $end)
            ->where('driver_unavailabilities.end_date', '>=', $start)
            ->with(['user:id,name,surname', 'user.driverProfile:id,user_id,color', 'leaveType:id,name'])
            ->select('driver_unavailabilities.*')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'driver_unavail_' . $item->id,
                    'type' => 'driver_unavailability',
                    'start_date' => $item->start_date->format('Y-m-d'),
                    'end_date' => $item->end_date->format('Y-m-d'),
                    'driver_id' => $item->user_id,
                    'driver_name' => $item->user->display_name ?? '',
                    'driver_color' => $item->user->driverProfile->color ?? '#6c757d',
                    'reason' => $item->leaveType->name ?? '',
                    'notes' => $item->notes,
                ];
            });

        // Vehicle unavailabilities: filter by company
        $vehicleQuery = VehicleUnavailability::join('vehicles', 'vehicle_unavailabilities.vehicle_id', '=', 'vehicles.id');
        if ($companyId) {
            $vehicleQuery->where('vehicles.company_id', $companyId);
        }
        $vehicleUnavailabilities = $vehicleQuery
            ->where('vehicle_unavailabilities.start_date', '<=', $end)
            ->where('vehicle_unavailabilities.end_date', '>=', $start)
            ->with(['vehicle:id,license_plate,brand,model', 'unavailabilityType:id,name'])
            ->select('vehicle_unavailabilities.*')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'vehicle_unavail_' . $item->id,
                    'type' => 'vehicle_unavailability',
                    'start_date' => $item->start_date->format('Y-m-d'),
                    'end_date' => $item->end_date->format('Y-m-d'),
                    'vehicle_id' => $item->vehicle_id,
                    'vehicle_plate' => $item->vehicle->license_plate ?? '',
                    'vehicle_label' => trim(($item->vehicle->brand ?? '') . ' ' . ($item->vehicle->model ?? '')),
                    'reason' => $item->unavailabilityType->name ?? '',
                    'notes' => $item->notes,
                ];
            });

        return response()->json([
            'driver_unavailabilities' => $driverUnavailabilities,
            'vehicle_unavailabilities' => $vehicleUnavailabilities,
        ]);
    }
}
