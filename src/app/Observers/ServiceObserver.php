<?php

namespace App\Observers;

use App\Models\Service;
use App\Models\ServiceStatus;
use App\Services\ServiceOverlapService;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;

class ServiceObserver
{
    protected ServiceOverlapService $overlapService;

    public function __construct(ServiceOverlapService $overlapService)
    {
        $this->overlapService = $overlapService;
    }

    /**
     * Handle the Service "updated" event.
     * Detect status changes and trigger Telegram notifications.
     */
    public function updated(Service $service): void
    {
        // Check if status_id changed
        if ($service->wasChanged('status_id')) {
            $this->handleStatusChange($service);
        }
    }

    /**
     * Handle status change: send Telegram notification if configured.
     */
    private function handleStatusChange(Service $service): void
    {
        try {
            // Load company settings
            $settings = \App\Models\Settings::withoutGlobalScopes()
                ->where('company_id', $service->company_id)
                ->first();

            // If no trigger status configured, skip notification
            if (!$settings || !$settings->telegram_trigger_status_id) {
                return;
            }

            // Check if new status matches the configured trigger status
            if ($service->status_id === $settings->telegram_trigger_status_id) {
                $notificationService = app(TelegramNotificationService::class);
                $notificationService->notifyServiceConfirmed($service);
            }
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error in ServiceObserver status change handler', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
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
