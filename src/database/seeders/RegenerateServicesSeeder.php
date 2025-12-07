<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\DressCode;
use App\Models\ServiceStatus;
use App\Models\ServiceType;
use App\Models\ServicePassenger;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\AccountingTransaction;
use App\Models\AccountingEntry;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegenerateServicesSeeder extends Seeder
{
    private $locations = [
        ['name' => 'Aeroporto Fiumicino', 'lat' => 41.8003, 'lng' => 12.2389],
        ['name' => 'Stazione Termini Roma', 'lat' => 41.9009, 'lng' => 12.5030],
        ['name' => 'Colosseo', 'lat' => 41.8902, 'lng' => 12.4922],
        ['name' => 'Vaticano', 'lat' => 41.9029, 'lng' => 12.4534],
        ['name' => 'Fontana di Trevi', 'lat' => 41.9009, 'lng' => 12.4833],
        ['name' => 'Piazza Venezia', 'lat' => 41.8957, 'lng' => 12.4826],
        ['name' => 'Hotel Excelsior Roma', 'lat' => 41.9074, 'lng' => 12.4896],
        ['name' => 'Centro Congressi Fiera Roma', 'lat' => 41.8281, 'lng' => 12.3617],
    ];

    private $firstNames = ['Mario', 'Luigi', 'Giovanni', 'Francesco', 'Antonio', 'Giuseppe', 'Marco', 'Luca', 'Paolo', 'Alessandro'];
    private $lastNames = ['Rossi', 'Russo', 'Ferrari', 'Esposito', 'Bianchi', 'Romano', 'Colombo', 'Ricci', 'Marino', 'Greco'];
    private $nationalities = ['Italiana', 'Americana', 'Inglese', 'Francese', 'Tedesca', 'Spagnola', 'Giapponese', 'Cinese'];

    public function run(): void
    {
        $this->command->info('Rigenerazione servizi esistenti...');

        $services = Service::with(['company', 'passengers', 'activities', 'accountingTransactions', 'tasks'])->get();

        foreach ($services as $service) {
            $this->command->info("Rigenerazione servizio ID: {$service->id} per azienda {$service->company->name}");

            // Get company-specific data
            $companyId = $service->company_id;

            $clients = User::where('company_id', $companyId)
                ->where('role', 'collaboratore')
                ->whereHas('clientProfile', function($q) {
                    $q->where('is_committente', true);
                })->get();

            $intermediaries = User::where('company_id', $companyId)
                ->where('is_intermediario', true)
                ->get();

            $suppliers = User::where('company_id', $companyId)
                ->where('role', 'collaboratore')
                ->whereHas('clientProfile', function($q) {
                    $q->where('is_fornitore', true);
                })->get();

            $vehicles = Vehicle::where('company_id', $companyId)->get();
            $drivers = User::where('company_id', $companyId)->where('role', 'driver')->get();
            $dressCodes = DressCode::where('company_id', $companyId)->get();
            $serviceStatuses = ServiceStatus::where('company_id', $companyId)->get();
            $serviceTypes = ServiceType::where('company_id', $companyId)->get();
            $activityTypes = ActivityType::where('company_id', $companyId)->get();
            $accountingEntries = AccountingEntry::where('company_id', $companyId)->get();

            if ($clients->isEmpty() || $vehicles->isEmpty() || $drivers->isEmpty() || $serviceStatuses->isEmpty()) {
                $this->command->warn("Dati insufficienti per l'azienda {$service->company->name}, skip servizio {$service->id}");
                continue;
            }

            // Generate random dates
            $pickupDate = Carbon::now()->addDays(rand(-30, 60))->setHour(rand(6, 20))->setMinute(rand(0, 59));
            $serviceDuration = rand(2, 8); // ore
            $dropoffDate = $pickupDate->copy()->addHours($serviceDuration);

            // Random locations
            $pickupLocation = $this->locations[array_rand($this->locations)];
            $dropoffLocation = $this->locations[array_rand($this->locations)];

            // Update service
            $service->update([
                'reference_number' => 'SRV-' . $service->company_id . '-' . str_pad($service->id, 4, '0', STR_PAD_LEFT),
                'client_id' => $clients->random()->id,
                'intermediary_id' => $intermediaries->isNotEmpty() ? $intermediaries->random()->id : null,
                'supplier_id' => $suppliers->isNotEmpty() ? $suppliers->random()->id : null,
                'passenger_count' => rand(1, 4),
                'contact_name' => $this->firstNames[array_rand($this->firstNames)] . ' ' . $this->lastNames[array_rand($this->lastNames)],
                'contact_phone' => '+39 ' . rand(300, 399) . ' ' . rand(1000000, 9999999),
                'service_type' => $serviceTypes->isNotEmpty() ? $serviceTypes->random()->name : 'Transfer',
                'vehicle_id' => $vehicles->random()->id,
                'vehicle_not_replaceable' => rand(0, 1) == 1,
                'external_driver_name' => null,
                'external_driver_phone' => null,
                'driver_not_replaceable' => rand(0, 1) == 1,
                'dress_code_id' => $dressCodes->isNotEmpty() ? $dressCodes->random()->id : null,
                'large_luggage' => rand(0, 3),
                'medium_luggage' => rand(0, 3),
                'small_luggage' => rand(0, 2),
                'baby_seat_infant' => rand(0, 1),
                'baby_seat_standard' => rand(0, 1),
                'baby_seat_booster' => rand(0, 1),
                'pickup_datetime' => $pickupDate,
                'pickup_location' => $pickupLocation['name'],
                'pickup_address' => $pickupLocation['name'] . ', Roma',
                'pickup_latitude' => $pickupLocation['lat'],
                'pickup_longitude' => $pickupLocation['lng'],
                'vehicle_departure_datetime' => $pickupDate->copy()->subMinutes(30),
                'dropoff_datetime' => $dropoffDate,
                'dropoff_location' => $dropoffLocation['name'],
                'dropoff_address' => $dropoffLocation['name'] . ', Roma',
                'dropoff_latitude' => $dropoffLocation['lat'],
                'dropoff_longitude' => $dropoffLocation['lng'],
                'vehicle_return_datetime' => $dropoffDate->copy()->addMinutes(30),
                'status_id' => $serviceStatuses->random()->id,
                'service_price' => rand(150, 800),
                'vat_rate' => 10,
                'card_fees_percentage' => 5,
                'deposit_percentage' => 30,
                'driver_compensation' => rand(80, 200),
                'intermediary_commission' => rand(20, 100),
                'expenses' => rand(10, 50),
                'notes' => 'Servizio rigenerato automaticamente il ' . now()->format('d/m/Y H:i'),
            ]);

            // Recalculate pricing
            $servicePrice = $service->service_price;
            $vatRate = $service->vat_rate;
            $cardFeesPerc = $service->card_fees_percentage;
            $depositPerc = $service->deposit_percentage;

            $taxableAmount = $servicePrice;
            $priceWithVat = $taxableAmount * (1 + $vatRate / 100);
            $priceWithVatAndCardFees = $priceWithVat * (1 + $cardFeesPerc / 100);

            $service->update([
                'deposit_amount' => round($priceWithVatAndCardFees * ($depositPerc / 100), 2),
                'balance_taxable' => round($taxableAmount * ((100 - $depositPerc) / 100), 2),
                'balance_handling_fees' => round($priceWithVat * ((100 - $depositPerc) / 100), 2),
                'balance_card_fees' => round($priceWithVatAndCardFees * ((100 - $depositPerc) / 100), 2),
            ]);

            // Sync drivers
            $driverCount = rand(1, 2);
            $selectedDrivers = $drivers->random(min($driverCount, $drivers->count()))->pluck('id');
            $service->drivers()->sync($selectedDrivers);

            // Regenerate passengers
            $service->passengers()->delete();
            for ($i = 0; $i < $service->passenger_count; $i++) {
                ServicePassenger::create([
                    'service_id' => $service->id,
                    'surname' => $this->lastNames[array_rand($this->lastNames)],
                    'name' => $this->firstNames[array_rand($this->firstNames)],
                    'phone' => '+39 ' . rand(300, 399) . ' ' . rand(1000000, 9999999),
                    'email' => strtolower($this->firstNames[array_rand($this->firstNames)]) . '@example.com',
                    'nationality' => $this->nationalities[array_rand($this->nationalities)],
                    'origin' => $this->locations[array_rand($this->locations)]['name'],
                    'carrier_reference' => 'AZ' . rand(100, 999),
                ]);
            }

            // Regenerate activities (0-2 activities)
            $service->activities()->delete();
            $activityCount = rand(0, 2);
            if ($activityTypes->isNotEmpty()) {
                for ($i = 0; $i < $activityCount; $i++) {
                    $activityStart = $pickupDate->copy()->addHours(rand(1, $serviceDuration - 1));
                    $activityEnd = $activityStart->copy()->addHours(rand(1, 2));

                    Activity::create([
                        'company_id' => $companyId,
                        'service_id' => $service->id,
                        'activity_type_id' => $activityTypes->random()->id,
                        'name' => 'Visita ' . $this->locations[array_rand($this->locations)]['name'],
                        'supplier_id' => $suppliers->isNotEmpty() ? $suppliers->random()->id : null,
                        'start_time' => $activityStart,
                        'end_time' => $activityEnd,
                        'cost' => rand(30, 150),
                        'cost_per_person' => rand(10, 50),
                        'payment_type' => ['INCLUSO', 'CLIENTE', 'AGENZIA'][array_rand(['INCLUSO', 'CLIENTE', 'AGENZIA'])],
                        'notes' => 'Attività generata automaticamente',
                    ]);
                }
            }

            // Regenerate accounting transactions (0-2 transactions)
            $service->accountingTransactions()->delete();
            if ($accountingEntries->isNotEmpty()) {
                $transactionCount = rand(0, 2);
                for ($i = 0; $i < $transactionCount; $i++) {
                    $transactionType = ['sale', 'purchase'][array_rand(['sale', 'purchase'])];
                    $installment = ['deposit', 'balance'][array_rand(['deposit', 'balance'])];

                    AccountingTransaction::create([
                        'company_id' => $companyId,
                        'service_id' => $service->id,
                        'transaction_date' => Carbon::now()->addDays(rand(-15, 15)),
                        'amount' => rand(100, 500),
                        'transaction_type' => $transactionType,
                        'installment' => $installment,
                        'accounting_entry_id' => $accountingEntries->random()->id,
                        'counterpart_id' => $transactionType === 'sale' ? $service->client_id : ($suppliers->isNotEmpty() ? $suppliers->random()->id : null),
                        'payment_type' => ['carta_di_credito', 'bonifico', 'contanti'][array_rand(['carta_di_credito', 'bonifico', 'contanti'])],
                        'status' => ['to_collect', 'collected', 'to_pay', 'paid'][array_rand(['to_collect', 'collected', 'to_pay', 'paid'])],
                    ]);
                }
            }

            // Regenerate tasks (0-3 tasks)
            $service->tasks()->delete();
            $taskCount = rand(0, 3);
            $taskUsers = User::where('company_id', $companyId)
                ->whereIn('role', ['admin', 'operator'])
                ->get();

            if ($taskUsers->isNotEmpty()) {
                for ($i = 0; $i < $taskCount; $i++) {
                    $task = Task::create([
                        'company_id' => $companyId,
                        'service_id' => $service->id,
                        'name' => ['Confermare prenotazione', 'Verificare disponibilità', 'Inviare documento'][array_rand(['Confermare prenotazione', 'Verificare disponibilità', 'Inviare documento'])],
                        'notes' => 'Task generato automaticamente',
                        'due_date' => $pickupDate->copy()->subDays(rand(1, 7)),
                        'status' => ['to_complete', 'completed'][array_rand(['to_complete', 'completed'])],
                    ]);

                    // Assign task to 1-2 users
                    $assignedUsers = $taskUsers->random(min(rand(1, 2), $taskUsers->count()))->pluck('id');
                    $task->assignedUsers()->sync($assignedUsers);
                }
            }

            $this->command->info("✓ Servizio {$service->id} rigenerato con successo");
        }

        $this->command->info('');
        $this->command->info('Rigenerazione completata! Totale servizi: ' . $services->count());
    }
}
