<?php

namespace App\Observers;

use App\Models\Service;
use App\Services\ServiceOverlapService;

class ServiceObserver
{
    protected ServiceOverlapService $overlapService;

    public function __construct(ServiceOverlapService $overlapService)
    {
        $this->overlapService = $overlapService;
    }

    /**
     * Handle the Service "deleted" event.
     * Clean up overlaps when a service is deleted.
     */
    public function deleted(Service $service): void
    {
        $this->overlapService->deleteOverlapsForService($service->id);
    }

    /**
     * Handle the Service "force deleted" event.
     * Clean up overlaps when a service is permanently deleted.
     */
    public function forceDeleted(Service $service): void
    {
        $this->overlapService->deleteOverlapsForService($service->id);
    }
}
