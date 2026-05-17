<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Service;
use App\Models\Settings;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshConfirmationTaskNames extends Command
{
    protected $signature = 'tasks:refresh-confirmation-names {--dry-run : Show changes without applying}';
    protected $description = 'Refresh confirmation task names using the current template from settings';

    public function handle(): int
    {
        // Backup tasks to a JSON file
        $tasks = Task::where('notes', 'like', '%Task di conferma automatico per l\'esperienza:%')->get();

        if ($tasks->isEmpty()) {
            $this->info('No confirmation tasks found.');
            return Command::SUCCESS;
        }

        // Create backup
        $backupPath = storage_path('app/task_backup_' . now()->format('Y-m-d_His') . '.json');
        file_put_contents($backupPath, $tasks->toJson(JSON_PRETTY_PRINT));
        $this->info("Backup saved to: {$backupPath}");
        $this->info("Found {$tasks->count()} confirmation tasks to refresh.");

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($tasks as $task) {
            try {
                // Load service
                $service = Service::with(['passengers', 'client', 'vehicle'])->find($task->service_id);
                if (!$service) {
                    $this->warn("  Task #{$task->id}: Service #{$task->service_id} not found");
                    $skipped++;
                    continue;
                }

                // Load settings for the company
                $settings = Settings::withoutGlobalScopes()->where('company_id', $service->company_id)->first();
                if (!$settings || !$settings->activity_confirmation_text) {
                    $this->warn("  Task #{$task->id}: No template configured for company #{$service->company_id}");
                    $skipped++;
                    continue;
                }

                // Find the activity: by activity_id first, fallback to name match from notes
                $activity = null;
                if ($task->activity_id) {
                    $activity = Activity::with(['activityType', 'supplier', 'supplier.clientProfile'])
                        ->find($task->activity_id);
                }

                if (!$activity) {
                    // Fallback: extract name from notes
                    if (!preg_match("/Task di conferma automatico per l'esperienza: (.+)/", $task->notes, $match)) {
                        $this->warn("  Task #{$task->id}: Could not extract activity name from notes");
                        $skipped++;
                        continue;
                    }
                    $activityName = $match[1];
                    $activity = Activity::where('service_id', $service->id)
                        ->where('name', $activityName)
                        ->with(['activityType', 'supplier', 'supplier.clientProfile'])
                        ->first();
                }

                if (!$activity) {
                    $activityName = $activityName ?? 'unknown';
                    $this->warn("  Task #{$task->id}: Activity '{$activityName}' not found in service #{$service->id}");
                    $skipped++;
                    continue;
                }

                // Update activity_id if not set
                if (!$task->activity_id && $activity) {
                    $task->update(['activity_id' => $activity->id]);
                }

                // Build new name from template
                $supplier = $activity->supplier
                    ? trim(($activity->supplier->name ?? '') . ' ' . ($activity->supplier->surname ?? ''))
                    : 'Fornitore non specificato';

                $supplierPhone = $activity->supplier->phone ?? '';
                $supplierEmail = $activity->supplier->clientProfile->operational_email
                    ?? $activity->supplier->email ?? '';

                $serviceRef = $service->reference_number ?: "Servizio #{$service->id}";

                $activityStartTime = $activity->start_time ? Carbon::parse($activity->start_time)->format('H:i') : '';
                $activityEndTime = $activity->end_time ? Carbon::parse($activity->end_time)->format('H:i') : '';
                $activityDate = $activity->start_time ? Carbon::parse($activity->start_time)->format('d/m/Y') : '';
                $activityTypeName = $activity->activityType->name ?? '';

                $pickupDate = $service->pickup_datetime ? Carbon::parse($service->pickup_datetime)->format('d/m/Y') : '';
                $pickupTime = $service->pickup_datetime ? Carbon::parse($service->pickup_datetime)->format('H:i') : '';

                $firstPassenger = $service->passengers->first();
                $passengerName = $firstPassenger
                    ? trim((strtoupper($firstPassenger->surname ?? '') . ' ' . ($firstPassenger->name ?? '')))
                    : '';

                $clientName = $service->client
                    ? trim(($service->client->name ?? '') . ' ' . ($service->client->surname ?? ''))
                    : '';

                $vehiclePlate = $service->vehicle->license_plate ?? '';

                $baseUrl = config('app.url', 'https://app.nccgest.it');
                $serviceLink = "{$baseUrl}/easyncc/services/{$service->id}/edit";
                $supplierLink = $activity->supplier_id ? "{$baseUrl}/easyncc/users/{$activity->supplier_id}/edit" : '';

                $newName = str_replace(
                    [
                        '{$fornitore$}', '{$servizio$}',
                        '{$nome_sosta$}', '{$tipo_sosta$}',
                        '{$ora_inizio$}', '{$ora_fine$}', '{$data_sosta$}',
                        '{$data_servizio$}', '{$ora_pickup$}',
                        '{$passeggero$}', '{$committente$}', '{$veicolo$}',
                        '{$telefono_fornitore$}', '{$email_fornitore$}',
                        '{$link_servizio$}', '{$link_fornitore$}',
                    ],
                    [
                        $supplier, $serviceRef,
                        $activity->name, $activityTypeName,
                        $activityStartTime, $activityEndTime, $activityDate,
                        $pickupDate, $pickupTime,
                        $passengerName, $clientName, $vehiclePlate,
                        $supplierPhone, $supplierEmail,
                        $serviceLink, $supplierLink,
                    ],
                    $settings->activity_confirmation_text
                );

                if ($this->option('dry-run')) {
                    $this->line("  Task #{$task->id}:");
                    $this->line("    OLD: {$task->name}");
                    $this->line("    NEW: {$newName}");
                } else {
                    $task->update(['name' => $newName]);
                    $updated++;
                }
            } catch (\Exception $e) {
                $this->error("  Task #{$task->id}: Error - {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        if ($this->option('dry-run')) {
            $this->info("DRY RUN complete. No changes applied.");
        } else {
            $this->info("Done. Updated: {$updated}, Skipped: {$skipped}, Errors: {$errors}");
        }

        return Command::SUCCESS;
    }
}
