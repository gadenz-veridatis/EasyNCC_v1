<?php

namespace App\Observers;

use App\Models\Service;
use App\Models\ServiceStatus;
use App\Models\Task;
use App\Services\ServiceOverlapService;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;
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

                // Create task "Accettare servizio" for assigned drivers
                $this->createAcceptTask($service);
            }
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error in ServiceObserver status change handler', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a task for drivers to accept the service via Telegram.
     */
    private function createAcceptTask(Service $service): void
    {
        try {
            $service->load(['drivers', 'client', 'passengers']);

            $pickupTime = $service->pickup_datetime
                ? Carbon::parse($service->pickup_datetime)->format('H:i')
                : '';
            $serviceType = $service->service_type ?? '';
            $passengerCount = $service->passengers ? $service->passengers->count() : 0;
            $contactName = '';
            if ($service->client) {
                $contactName = trim(($service->client->name ?? '') . ' ' . ($service->client->surname ?? ''));
            }
            $clientName = $service->client->business_name ?? $contactName;

            $titleParts = array_filter([$pickupTime, $serviceType, "{$passengerCount} pax", $contactName, $clientName]);
            $taskTitle = "[TG:ACCEPT:{$service->id}] " . implode(' | ', $titleParts) . ' - Accettare servizio';

            $task = Task::create([
                'company_id' => $service->company_id,
                'service_id' => $service->id,
                'name' => $taskTitle,
                'due_date' => $service->pickup_datetime
                    ? Carbon::parse($service->pickup_datetime)->toDateString()
                    : null,
                'notes' => "Task creato automaticamente alla notifica Telegram del servizio #{$service->id}",
                'status' => 'to_complete',
            ]);

            $driverIds = $service->drivers->pluck('id')->toArray();
            if (!empty($driverIds)) {
                $task->assignedUsers()->sync($driverIds);
            }

            Log::channel('telegram')->info('Accept task created', [
                'task_id' => $task->id,
                'service_id' => $service->id,
            ]);
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error creating accept task', [
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
