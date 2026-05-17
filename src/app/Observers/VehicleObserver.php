<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Models\Service;
use App\Models\ServiceOverlap;
use App\Services\ServiceOverlapService;
use Illuminate\Support\Facades\Log;

class VehicleObserver
{
    protected ServiceOverlapService $overlapService;

    public function __construct(ServiceOverlapService $overlapService)
    {
        $this->overlapService = $overlapService;
    }

    /**
     * Handle the Vehicle "updated" event.
     * Recalculate overlaps when allow_overlapping changes.
     */
    public function updated(Vehicle $vehicle): void
    {
        if (!$vehicle->wasChanged('allow_overlapping')) {
            return;
        }

        try {
            if ($vehicle->allow_overlapping) {
                $this->onBecameOverlappable($vehicle);
            } else {
                $this->onBecameNonOverlappable($vehicle);
            }
        } catch (\Exception $e) {
            Log::error('VehicleObserver: error recalculating overlaps', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Vehicle changed from non-overlappable to overlappable:
     * remove vehicle overlap records since they're no longer relevant.
     */
    private function onBecameOverlappable(Vehicle $vehicle): void
    {
        // Degrade 'both' records to 'driver' (the driver overlap is still valid)
        ServiceOverlap::where('vehicle_id', $vehicle->id)
            ->where('overlap_type', 'both')
            ->update(['overlap_type' => 'driver', 'vehicle_id' => null]);

        // Delete pure vehicle overlap records
        ServiceOverlap::where('vehicle_id', $vehicle->id)
            ->where('overlap_type', 'vehicle')
            ->delete();
    }

    /**
     * Vehicle changed from overlappable to non-overlappable:
     * recalculate overlaps for all active services using this vehicle.
     */
    private function onBecameNonOverlappable(Vehicle $vehicle): void
    {
        $services = Service::where('vehicle_id', $vehicle->id)
            ->where('company_id', $vehicle->company_id)
            ->whereNull('deleted_at')
            ->where('vehicle_return_datetime', '>=', now())
            ->get();

        foreach ($services as $service) {
            $this->overlapService->recalculateOverlapsForService($service);
        }
    }
}
