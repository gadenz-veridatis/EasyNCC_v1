<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceOverlap;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiceOverlapService
{
    /**
     * Check for overlaps before saving a service.
     * Returns an array of detected overlaps without saving them.
     *
     * @param array $serviceData The service data being saved
     * @param int|null $excludeServiceId Service ID to exclude (for updates)
     * @return array Array of overlap information
     */
    public function checkOverlaps(array $serviceData, ?int $excludeServiceId = null): array
    {
        $overlaps = [];

        $vehicleDeparture = $serviceData['vehicle_departure_datetime'] ?? null;
        $vehicleReturn = $serviceData['vehicle_return_datetime'] ?? null;
        $pickupDatetime = $serviceData['pickup_datetime'] ?? null;
        $dropoffDatetime = $serviceData['dropoff_datetime'] ?? null;
        $vehicleId = $serviceData['vehicle_id'] ?? null;
        $driverIds = $serviceData['driver_ids'] ?? [];
        $companyId = $serviceData['company_id'] ?? null;

        if (!$vehicleDeparture || !$vehicleReturn || !$companyId) {
            return $overlaps;
        }

        // Compute effective busy period: broadest range across all datetime fields
        $effectiveStart = $vehicleDeparture;
        $effectiveEnd = $vehicleReturn;

        if ($pickupDatetime && $pickupDatetime < $effectiveStart) {
            $effectiveStart = $pickupDatetime;
        }
        if ($dropoffDatetime && $dropoffDatetime > $effectiveEnd) {
            $effectiveEnd = $dropoffDatetime;
        }

        // Check vehicle overlaps
        if ($vehicleId) {
            $vehicleOverlaps = $this->checkVehicleOverlaps(
                $vehicleId,
                $effectiveStart,
                $effectiveEnd,
                $companyId,
                $excludeServiceId
            );
            $overlaps = array_merge($overlaps, $vehicleOverlaps);
        }

        // Check driver overlaps
        if (!empty($driverIds)) {
            $driverOverlaps = $this->checkDriverOverlaps(
                $driverIds,
                $effectiveStart,
                $effectiveEnd,
                $companyId,
                $excludeServiceId
            );
            $overlaps = array_merge($overlaps, $driverOverlaps);
        }

        // Merge overlaps for same service (vehicle + driver = both)
        $overlaps = $this->mergeOverlaps($overlaps);

        return $overlaps;
    }

    /**
     * Check for vehicle overlaps.
     */
    protected function checkVehicleOverlaps(
        int $vehicleId,
        string $departure,
        string $return,
        int $companyId,
        ?int $excludeServiceId
    ): array {
        $overlaps = [];

        // Check if vehicle allows overlapping
        $vehicle = Vehicle::find($vehicleId);
        if (!$vehicle || $vehicle->allow_overlapping) {
            return $overlaps;
        }

        // Find overlapping services for this vehicle
        // Use the broadest time range: LEAST(pickup, departure) to GREATEST(dropoff, return)
        $query = Service::where('company_id', $companyId)
            ->where('vehicle_id', $vehicleId)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($departure, $return) {
                // Time overlap: effective_start < other_effective_end AND effective_end > other_effective_start
                $q->whereRaw('LEAST(COALESCE(pickup_datetime, vehicle_departure_datetime), vehicle_departure_datetime) < ?', [$return])
                  ->whereRaw('GREATEST(COALESCE(dropoff_datetime, vehicle_return_datetime), vehicle_return_datetime) > ?', [$departure]);
            });

        if ($excludeServiceId) {
            $query->where('id', '!=', $excludeServiceId);
        }

        // Exclude cancelled/completed services if status logic is needed
        // For now, include all active services

        $overlappingServices = $query->get();

        foreach ($overlappingServices as $service) {
            $overlaps[] = [
                'overlapping_service_id' => $service->id,
                'overlapping_service_reference' => $service->reference_number,
                'overlap_type' => 'vehicle',
                'vehicle_id' => $vehicleId,
                'vehicle_plate' => $vehicle->license_plate,
                'vehicle_brand' => $vehicle->brand,
                'vehicle_model' => $vehicle->model,
                'driver_id' => null,
                'driver_name' => null,
                'service_departure' => $service->vehicle_departure_datetime,
                'service_return' => $service->vehicle_return_datetime,
            ];
        }

        return $overlaps;
    }

    /**
     * Check for driver overlaps.
     */
    protected function checkDriverOverlaps(
        array $driverIds,
        string $departure,
        string $return,
        int $companyId,
        ?int $excludeServiceId
    ): array {
        $overlaps = [];

        // Batch load all drivers with profiles (1 query instead of N)
        $drivers = User::with('driverProfile')
            ->whereIn('id', $driverIds)
            ->get()
            ->keyBy('id');

        // Filter to only non-overlapping drivers
        $checkDriverIds = [];
        foreach ($driverIds as $driverId) {
            $driver = $drivers->get($driverId);
            if ($driver && $driver->driverProfile && !$driver->driverProfile->allow_overlapping) {
                $checkDriverIds[] = $driverId;
            }
        }

        if (empty($checkDriverIds)) {
            return $overlaps;
        }

        // Single query for all driver overlaps
        // Use the broadest time range: LEAST(pickup, departure) to GREATEST(dropoff, return)
        $query = Service::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereHas('drivers', function ($q) use ($checkDriverIds) {
                $q->whereIn('users.id', $checkDriverIds);
            })
            ->where(function ($q) use ($departure, $return) {
                $q->whereRaw('LEAST(COALESCE(pickup_datetime, vehicle_departure_datetime), vehicle_departure_datetime) < ?', [$return])
                  ->whereRaw('GREATEST(COALESCE(dropoff_datetime, vehicle_return_datetime), vehicle_return_datetime) > ?', [$departure]);
            });

        if ($excludeServiceId) {
            $query->where('id', '!=', $excludeServiceId);
        }

        // Load only the drivers from our checklist
        $overlappingServices = $query->with(['drivers' => function ($q) use ($checkDriverIds) {
            $q->whereIn('users.id', $checkDriverIds);
        }])->get();

        // Build overlap entries per driver per service
        foreach ($overlappingServices as $service) {
            foreach ($service->drivers as $serviceDriver) {
                if (in_array($serviceDriver->id, $checkDriverIds)) {
                    $driverModel = $drivers->get($serviceDriver->id);
                    $overlaps[] = [
                        'overlapping_service_id' => $service->id,
                        'overlapping_service_reference' => $service->reference_number,
                        'overlap_type' => 'driver',
                        'vehicle_id' => null,
                        'vehicle_plate' => null,
                        'vehicle_brand' => null,
                        'vehicle_model' => null,
                        'driver_id' => $serviceDriver->id,
                        'driver_name' => $driverModel->surname . ' ' . $driverModel->name,
                        'service_departure' => $service->vehicle_departure_datetime,
                        'service_return' => $service->vehicle_return_datetime,
                    ];
                }
            }
        }

        return $overlaps;
    }

    /**
     * Merge overlaps where the same service overlaps for both vehicle and driver.
     */
    protected function mergeOverlaps(array $overlaps): array
    {
        $merged = [];
        $byService = [];

        foreach ($overlaps as $overlap) {
            $serviceId = $overlap['overlapping_service_id'];
            if (!isset($byService[$serviceId])) {
                $byService[$serviceId] = $overlap;
            } else {
                // Merge: if one is vehicle and one is driver, make it 'both'
                $existing = $byService[$serviceId];
                if ($existing['overlap_type'] !== $overlap['overlap_type']) {
                    $byService[$serviceId]['overlap_type'] = 'both';
                    // Keep both vehicle and driver info
                    if ($overlap['vehicle_id']) {
                        $byService[$serviceId]['vehicle_id'] = $overlap['vehicle_id'];
                        $byService[$serviceId]['vehicle_plate'] = $overlap['vehicle_plate'];
                        $byService[$serviceId]['vehicle_brand'] = $overlap['vehicle_brand'];
                        $byService[$serviceId]['vehicle_model'] = $overlap['vehicle_model'];
                    }
                    if ($overlap['driver_id']) {
                        $byService[$serviceId]['driver_id'] = $overlap['driver_id'];
                        $byService[$serviceId]['driver_name'] = $overlap['driver_name'];
                    }
                }
            }
        }

        return array_values($byService);
    }

    /**
     * Save overlaps for a service.
     * This should be called after the service is saved.
     *
     * @param Service $service The saved service
     * @param array $overlaps The detected overlaps
     * @param bool $confirmedByUser Whether user confirmed the overlaps
     */
    public function saveOverlaps(Service $service, array $overlaps, bool $confirmedByUser = true): void
    {
        // First, delete existing overlaps for this service
        $this->deleteOverlapsForService($service->id);

        // Insert new overlaps
        foreach ($overlaps as $overlap) {
            ServiceOverlap::create([
                'service_id' => $service->id,
                'overlapping_service_id' => $overlap['overlapping_service_id'],
                'overlap_type' => $overlap['overlap_type'],
                'driver_id' => $overlap['driver_id'],
                'vehicle_id' => $overlap['vehicle_id'],
                'confirmed_by_user' => $confirmedByUser,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Delete all overlaps for a service.
     *
     * @param int $serviceId
     */
    public function deleteOverlapsForService(int $serviceId): void
    {
        ServiceOverlap::where('service_id', $serviceId)
            ->orWhere('overlapping_service_id', $serviceId)
            ->delete();
    }

    /**
     * Recalculate overlaps for a service after it's been saved.
     * This is called by the Observer.
     *
     * @param Service $service
     */
    public function recalculateOverlapsForService(Service $service): void
    {
        // Get driver IDs from the service
        $driverIds = $service->drivers()->pluck('users.id')->toArray();

        $serviceData = [
            'vehicle_departure_datetime' => $service->vehicle_departure_datetime?->format('Y-m-d H:i:s'),
            'vehicle_return_datetime' => $service->vehicle_return_datetime?->format('Y-m-d H:i:s'),
            'pickup_datetime' => $service->pickup_datetime?->format('Y-m-d H:i:s'),
            'dropoff_datetime' => $service->dropoff_datetime?->format('Y-m-d H:i:s'),
            'vehicle_id' => $service->vehicle_id,
            'driver_ids' => $driverIds,
            'company_id' => $service->company_id,
        ];

        $overlaps = $this->checkOverlaps($serviceData, $service->id);

        // Save the overlaps - mark as confirmed since service is already saved
        $this->saveOverlaps($service, $overlaps, true);
    }

    /**
     * Get overlaps for a service.
     *
     * @param int $serviceId
     * @return Collection
     */
    public function getOverlapsForService(int $serviceId): Collection
    {
        return ServiceOverlap::with(['overlappingService', 'driver', 'vehicle'])
            ->where('service_id', $serviceId)
            ->orWhere('overlapping_service_id', $serviceId)
            ->get();
    }
}
