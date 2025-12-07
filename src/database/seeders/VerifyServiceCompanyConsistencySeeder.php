<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class VerifyServiceCompanyConsistencySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Verifica coerenza dati azienda per i servizi...');
        $this->command->info('');

        $services = Service::with([
            'company',
            'client',
            'intermediary',
            'supplier',
            'vehicle',
            'drivers',
            'dressCode',
            'status',
            'passengers',
            'activities.activityType',
            'activities.supplier',
            'accountingTransactions.accountingEntry',
            'accountingTransactions.counterpart',
            'tasks.assignedUsers'
        ])->get();

        $totalIssues = 0;

        foreach ($services as $service) {
            $this->command->info("Servizio ID: {$service->id} - {$service->reference_number}");
            $this->command->info("  Azienda: {$service->company->name} (ID: {$service->company_id})");

            $issues = [];

            // Check client
            if ($service->client && $service->client->company_id !== $service->company_id) {
                $issues[] = "  ❌ Cliente appartiene ad azienda diversa (Client company_id: {$service->client->company_id})";
            } else if ($service->client) {
                $this->command->info("  ✓ Cliente: {$service->client->name}");
            }

            // Check intermediary
            if ($service->intermediary && $service->intermediary->company_id !== $service->company_id) {
                $issues[] = "  ❌ Intermediario appartiene ad azienda diversa (Intermediary company_id: {$service->intermediary->company_id})";
            } else if ($service->intermediary) {
                $this->command->info("  ✓ Intermediario: {$service->intermediary->name}");
            }

            // Check supplier
            if ($service->supplier && $service->supplier->company_id !== $service->company_id) {
                $issues[] = "  ❌ Fornitore appartiene ad azienda diversa (Supplier company_id: {$service->supplier->company_id})";
            } else if ($service->supplier) {
                $this->command->info("  ✓ Fornitore: {$service->supplier->name}");
            }

            // Check vehicle
            if ($service->vehicle && $service->vehicle->company_id !== $service->company_id) {
                $issues[] = "  ❌ Veicolo appartiene ad azienda diversa (Vehicle company_id: {$service->vehicle->company_id})";
            } else if ($service->vehicle) {
                $this->command->info("  ✓ Veicolo: {$service->vehicle->license_plate}");
            }

            // Check drivers
            foreach ($service->drivers as $driver) {
                if ($driver->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Driver '{$driver->name}' appartiene ad azienda diversa (Driver company_id: {$driver->company_id})";
                }
            }
            if ($service->drivers->isNotEmpty()) {
                $this->command->info("  ✓ Drivers: " . $service->drivers->count());
            }

            // Check dress code
            if ($service->dressCode && $service->dressCode->company_id !== $service->company_id) {
                $issues[] = "  ❌ Dress code appartiene ad azienda diversa (DressCode company_id: {$service->dressCode->company_id})";
            } else if ($service->dressCode) {
                $this->command->info("  ✓ Dress Code: {$service->dressCode->name}");
            }

            // Check status
            if ($service->status && $service->status->company_id !== $service->company_id) {
                $issues[] = "  ❌ Stato servizio appartiene ad azienda diversa (Status company_id: {$service->status->company_id})";
            } else if ($service->status) {
                $this->command->info("  ✓ Stato: {$service->status->name}");
            }

            // Check passengers
            $this->command->info("  ✓ Passeggeri: " . $service->passengers->count());

            // Check activities
            foreach ($service->activities as $activity) {
                if ($activity->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Activity '{$activity->name}' appartiene ad azienda diversa (Activity company_id: {$activity->company_id})";
                }
                if ($activity->activityType && $activity->activityType->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Activity Type '{$activity->activityType->name}' appartiene ad azienda diversa";
                }
                if ($activity->supplier && $activity->supplier->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Activity Supplier '{$activity->supplier->name}' appartiene ad azienda diversa";
                }
            }
            if ($service->activities->isNotEmpty()) {
                $this->command->info("  ✓ Attività: " . $service->activities->count());
            }

            // Check accounting transactions
            foreach ($service->accountingTransactions as $transaction) {
                if ($transaction->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Transaction ID {$transaction->id} appartiene ad azienda diversa (Transaction company_id: {$transaction->company_id})";
                }
                if ($transaction->accountingEntry && $transaction->accountingEntry->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Accounting Entry appartiene ad azienda diversa";
                }
                if ($transaction->counterpart && $transaction->counterpart->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Transaction Counterpart appartiene ad azienda diversa";
                }
            }
            if ($service->accountingTransactions->isNotEmpty()) {
                $this->command->info("  ✓ Transazioni contabili: " . $service->accountingTransactions->count());
            }

            // Check tasks
            foreach ($service->tasks as $task) {
                if ($task->company_id !== $service->company_id) {
                    $issues[] = "  ❌ Task '{$task->name}' appartiene ad azienda diversa (Task company_id: {$task->company_id})";
                }
                foreach ($task->assignedUsers as $user) {
                    if ($user->company_id !== $service->company_id) {
                        $issues[] = "  ❌ Task assigned user '{$user->name}' appartiene ad azienda diversa";
                    }
                }
            }
            if ($service->tasks->isNotEmpty()) {
                $this->command->info("  ✓ Tasks: " . $service->tasks->count());
            }

            // Print issues
            if (count($issues) > 0) {
                $this->command->error('');
                $this->command->error("  PROBLEMI TROVATI:");
                foreach ($issues as $issue) {
                    $this->command->error($issue);
                }
                $totalIssues += count($issues);
            } else {
                $this->command->info("  ✓ Nessun problema di coerenza trovato");
            }

            $this->command->info('');
        }

        $this->command->info('====================================');
        if ($totalIssues === 0) {
            $this->command->info("✓ Verifica completata: TUTTI I DATI SONO COERENTI!");
        } else {
            $this->command->error("✗ Verifica completata: Trovati {$totalIssues} problemi di coerenza");
        }
        $this->command->info('====================================');
    }
}
