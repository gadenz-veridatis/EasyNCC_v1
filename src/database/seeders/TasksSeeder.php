<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\Service;
use App\Models\User;
use App\Models\Company;

class TasksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = Company::all();

        foreach ($companies as $company) {
            // Get all services of this company
            $services = Service::where('company_id', $company->id)
                ->orderBy('id', 'asc')
                ->get();

            // Get users of this company
            $drivers = User::where('company_id', $company->id)
                ->where('role', 'driver')
                ->get();

            $admins = User::where('company_id', $company->id)
                ->whereIn('role', ['admin', 'operator'])
                ->get();

            // Skip if no drivers available
            if ($drivers->isEmpty()) {
                continue;
            }

            // Define 6 tasks that can be associated with services
            $serviceTasks = [
                [
                    'name' => 'Verificare documenti veicolo prima del servizio',
                    'due_date' => now()->subDays(2),
                    'assigned_to' => $drivers->first()->id,
                    'status' => 'to_complete',
                    'notes' => 'Controllare assicurazione e revisione del veicolo prima della partenza',
                ],
                [
                    'name' => 'Confermare prenotazione con il cliente',
                    'due_date' => now()->addDays(3),
                    'assigned_to' => $admins->isNotEmpty() ? $admins->first()->id : null,
                    'status' => 'to_complete',
                    'notes' => 'Inviare email di conferma con dettagli del servizio e orario di pickup',
                ],
                [
                    'name' => 'Preparare itinerario e calcolare pedaggi',
                    'due_date' => now()->subDays(5),
                    'assigned_to' => $drivers->count() > 1 ? $drivers[1]->id : $drivers->first()->id,
                    'status' => 'completed',
                    'notes' => 'Itinerario preparato, costo pedaggi: €45.00',
                ],
                [
                    'name' => 'Sanificare veicolo dopo il servizio',
                    'due_date' => now()->addDay(),
                    'assigned_to' => $drivers->first()->id,
                    'status' => 'to_complete',
                    'notes' => 'Sanificazione completa abitacolo richiesta dal cliente',
                ],
                [
                    'name' => 'Verificare disponibilità seggiolini baby',
                    'due_date' => now()->addDays(7),
                    'assigned_to' => $admins->isNotEmpty() ? $admins->first()->id : null,
                    'status' => 'to_complete',
                    'notes' => 'Il cliente ha richiesto 2 seggiolini tipo booster',
                ],
                [
                    'name' => 'Organizzare servizio sostitutivo',
                    'due_date' => now()->addDays(2),
                    'assigned_to' => $admins->isNotEmpty() ? $admins->first()->id : null,
                    'status' => 'cancelled',
                    'notes' => 'Servizio annullato dal cliente',
                ],
            ];

            // Create 6 tasks associated with services (if services exist, cycle through them)
            foreach ($serviceTasks as $index => $taskData) {
                $serviceId = null;

                // If there are services, associate the task with one (cycle through available services)
                if ($services->isNotEmpty()) {
                    $serviceIndex = $index % $services->count();
                    $serviceId = $services[$serviceIndex]->id;
                }

                Task::create([
                    'company_id' => $company->id,
                    'service_id' => $serviceId,
                    'name' => $taskData['name'],
                    'due_date' => $taskData['due_date'],
                    'assigned_to' => $taskData['assigned_to'],
                    'status' => $taskData['status'],
                    'notes' => $taskData['notes'],
                ]);
            }

            // Task 7 - NON associato a servizio (tra 5 giorni)
            Task::create([
                'company_id' => $company->id,
                'service_id' => null,
                'name' => 'Rinnovare assicurazione flotta veicoli',
                'due_date' => now()->addDays(5),
                'assigned_to' => $admins->isNotEmpty() ? $admins->first()->id : null,
                'status' => 'to_complete',
                'notes' => 'Scadenza assicurazione: ' . now()->addDays(10)->format('d/m/Y'),
            ]);

            // Task 8 - NON associato a servizio (scaduto)
            Task::create([
                'company_id' => $company->id,
                'service_id' => null,
                'name' => 'Aggiornare listino prezzi',
                'due_date' => now()->subDays(3),
                'assigned_to' => $admins->isNotEmpty() ? $admins->first()->id : null,
                'status' => 'to_complete',
                'notes' => 'Rivedere prezzi per la stagione estiva e aggiornare sul sistema',
            ]);
        }
    }
}
