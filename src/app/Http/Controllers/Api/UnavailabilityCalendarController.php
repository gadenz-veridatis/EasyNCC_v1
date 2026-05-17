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

        // Driver unavailabilities: direct company_id filter (no JOIN needed)
        $driverQuery = DriverUnavailability::query();
        if ($companyId) {
            $driverQuery->where('company_id', $companyId);
        }
        $driverUnavailabilities = $driverQuery
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->with(['user:id,name,surname,nickname', 'user.driverProfile:user_id,color', 'leaveType:id,name'])
            ->get()
            ->map(function ($item) {
                $allDay = (bool) $item->all_day;
                return [
                    'id' => 'driver_unavail_' . $item->id,
                    'type' => 'driver_unavailability',
                    'start_date' => $allDay ? $item->start_date->format('Y-m-d') : $item->start_date->format('Y-m-d\TH:i:s'),
                    'end_date' => $allDay ? $item->end_date->format('Y-m-d') : $item->end_date->format('Y-m-d\TH:i:s'),
                    'all_day' => $allDay,
                    'driver_id' => $item->user_id,
                    'driver_name' => $item->user->display_name ?? '',
                    'driver_color' => $item->user->driverProfile->color ?? '#6c757d',
                    'leave_type_id' => $item->leave_type_id,
                    'reason' => $item->leaveType->name ?? '',
                    'notes' => $item->notes,
                ];
            });

        // Vehicle unavailabilities: direct company_id filter (no JOIN needed)
        $vehicleQuery = VehicleUnavailability::query();
        if ($companyId) {
            $vehicleQuery->where('company_id', $companyId);
        }
        $vehicleUnavailabilities = $vehicleQuery
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->with(['vehicle:id,license_plate,brand,model', 'unavailabilityType:id,name'])
            ->get()
            ->map(function ($item) {
                $allDay = (bool) $item->all_day;
                return [
                    'id' => 'vehicle_unavail_' . $item->id,
                    'type' => 'vehicle_unavailability',
                    'start_date' => $allDay ? $item->start_date->format('Y-m-d') : $item->start_date->format('Y-m-d\TH:i:s'),
                    'end_date' => $allDay ? $item->end_date->format('Y-m-d') : $item->end_date->format('Y-m-d\TH:i:s'),
                    'all_day' => $allDay,
                    'vehicle_id' => $item->vehicle_id,
                    'vehicle_plate' => $item->vehicle->license_plate ?? '',
                    'vehicle_label' => trim(($item->vehicle->brand ?? '') . ' ' . ($item->vehicle->model ?? '')),
                    'vehicle_unavailability_type_id' => $item->vehicle_unavailability_type_id,
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
