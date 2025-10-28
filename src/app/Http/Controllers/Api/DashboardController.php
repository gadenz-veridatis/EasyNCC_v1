<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();

        // Total services
        $totalServices = Service::count();

        // Services for today
        $todayServices = Service::whereDate('pickup_datetime', Carbon::today())->count();

        // Available vehicles
        $availableVehicles = Vehicle::where('status', 'in_service')->count();

        // Available drivers
        $availableDrivers = User::where('role', 'driver')
            ->where('is_active', true)
            ->count();

        return response()->json([
            'total_services' => $totalServices,
            'today_services' => $todayServices,
            'available_vehicles' => $availableVehicles,
            'available_drivers' => $availableDrivers,
        ]);
    }

    /**
     * Get upcoming services scheduled for today and tomorrow
     */
    public function upcomingServices(): JsonResponse
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDay();

        $services = Service::with([
            'vehicle',
            'client',
            'drivers',
            'status',
            'company'
        ])
        ->whereBetween('pickup_datetime', [$startDate, $endDate])
        ->orderBy('pickup_datetime', 'asc')
        ->limit(20)
        ->get();

        return response()->json([
            'upcoming_services' => $services,
            'count' => $services->count(),
        ]);
    }
}
