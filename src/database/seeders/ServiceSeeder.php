<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            // Get or create a committente user for this company
            $client = \App\Models\User::firstOrCreate(
                [
                    'email' => 'cliente1@' . strtolower(str_replace(' ', '', $company->name)) . '.it',
                    'company_id' => $company->id,
                ],
                [
                    'name' => 'Cliente',
                    'surname' => 'Demo',
                    'role' => 'committente',
                    'is_active' => true,
                    'password' => bcrypt('password'),
                ]
            );

            // Get vehicles and drivers for this company
            $vehicles = $company->vehicles()->get();
            $drivers = $company->users()->where('role', 'driver')->get();
            $statuses = \App\Models\ServiceStatus::where('company_id', $company->id)->get();
            $dressCodes = \App\Models\DressCode::where('company_id', $company->id)->get();

            if ($vehicles->isEmpty() || $drivers->isEmpty() || $statuses->isEmpty()) {
                continue;
            }

            // Service 1: Confirmed service for tomorrow
            $service1 = \App\Models\Service::create([
                'company_id' => $company->id,
                'reference_number' => 'SRV-' . str_pad($company->id * 1000 + 1, 6, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'passenger_count' => 2,
                'service_type' => 'Trasferimento Aeroportuale',
                'vehicle_type' => 'Berlina Luxury',
                'vehicle_id' => $vehicles->first()->id,
                'vehicle_not_replaceable' => false,
                'driver_not_replaceable' => false,
                'dress_code_id' => $dressCodes->isNotEmpty() ? $dressCodes->first()->id : null,
                'large_luggage' => 2,
                'medium_luggage' => 1,
                'small_luggage' => 0,
                'baby_seat_infant' => 0,
                'baby_seat_standard' => 0,
                'baby_seat_booster' => 0,
                'pickup_datetime' => Carbon::tomorrow()->setTime(10, 0),
                'pickup_address' => 'Aeroporto Fiumicino - Terminal 3, Fiumicino RM',
                'pickup_latitude' => 41.8003,
                'pickup_longitude' => 12.2389,
                'vehicle_departure_datetime' => Carbon::tomorrow()->setTime(9, 30),
                'dropoff_datetime' => Carbon::tomorrow()->setTime(11, 0),
                'dropoff_address' => 'Via Veneto 50, Roma RM',
                'dropoff_latitude' => 41.9074,
                'dropoff_longitude' => 12.4914,
                'vehicle_return_datetime' => Carbon::tomorrow()->setTime(11, 30),
                'status_id' => $statuses->where('name', 'confermato')->first()->id,
                'service_price' => 85.00,
                'driver_compensation' => 40.00,
                'intermediary_commission' => 0,
                'expenses' => 5.00,
                'notes' => 'Cliente richiede assistenza con i bagagli',
            ]);

            // Assign driver to service
            if ($drivers->isNotEmpty()) {
                $service1->drivers()->attach($drivers->first()->id);
            }

            // Service 2: In progress service for today
            if ($vehicles->count() > 1) {
                $service2 = \App\Models\Service::create([
                    'company_id' => $company->id,
                    'reference_number' => 'SRV-' . str_pad($company->id * 1000 + 2, 6, '0', STR_PAD_LEFT),
                    'client_id' => $client->id,
                    'passenger_count' => 4,
                    'service_type' => 'Evento Aziendale',
                    'vehicle_type' => 'Berlina Luxury',
                    'vehicle_id' => $vehicles->skip(1)->first()->id,
                    'vehicle_not_replaceable' => true,
                    'driver_not_replaceable' => false,
                    'dress_code_id' => $dressCodes->isNotEmpty() ? $dressCodes->where('name', 'Abito Elegante')->first()->id ?? $dressCodes->first()->id : null,
                    'large_luggage' => 0,
                    'medium_luggage' => 2,
                    'small_luggage' => 4,
                    'baby_seat_infant' => 0,
                    'baby_seat_standard' => 0,
                    'baby_seat_booster' => 0,
                    'pickup_datetime' => Carbon::today()->setTime(18, 0),
                    'pickup_address' => 'Hotel Excelsior, Via Veneto 125, Roma RM',
                    'pickup_latitude' => 41.9078,
                    'pickup_longitude' => 12.4901,
                    'vehicle_departure_datetime' => Carbon::today()->setTime(17, 45),
                    'dropoff_datetime' => Carbon::today()->setTime(19, 0),
                    'dropoff_address' => 'Palazzo dei Congressi, EUR, Roma RM',
                    'dropoff_latitude' => 41.8336,
                    'dropoff_longitude' => 12.4679,
                    'vehicle_return_datetime' => Carbon::today()->setTime(19, 30),
                    'status_id' => $statuses->where('name', 'in corso')->first()->id,
                    'service_price' => 120.00,
                    'driver_compensation' => 55.00,
                    'intermediary_commission' => 10.00,
                    'expenses' => 8.00,
                    'notes' => 'Evento aziendale importante - massima puntualità',
                ]);

                if ($drivers->isNotEmpty()) {
                    $service2->drivers()->attach($drivers->first()->id);
                }
            }

            // Service 3: Preventivo for next week
            if ($vehicles->count() > 2) {
                $service3 = \App\Models\Service::create([
                    'company_id' => $company->id,
                    'reference_number' => 'SRV-' . str_pad($company->id * 1000 + 3, 6, '0', STR_PAD_LEFT),
                    'client_id' => $client->id,
                    'passenger_count' => 1,
                    'service_type' => 'Disponibilità Giornaliera',
                    'vehicle_type' => 'Berlina Luxury',
                    'vehicle_id' => $vehicles->skip(2)->first()->id,
                    'vehicle_not_replaceable' => false,
                    'driver_not_replaceable' => false,
                    'dress_code_id' => $dressCodes->isNotEmpty() ? $dressCodes->where('name', 'Business Casual')->first()->id ?? $dressCodes->first()->id : null,
                    'large_luggage' => 1,
                    'medium_luggage' => 0,
                    'small_luggage' => 1,
                    'baby_seat_infant' => 0,
                    'baby_seat_standard' => 0,
                    'baby_seat_booster' => 0,
                    'pickup_datetime' => Carbon::now()->addDays(7)->setTime(8, 0),
                    'pickup_address' => 'Via del Corso 300, Roma RM',
                    'pickup_latitude' => 41.9028,
                    'pickup_longitude' => 12.4784,
                    'vehicle_departure_datetime' => Carbon::now()->addDays(7)->setTime(7, 45),
                    'dropoff_datetime' => Carbon::now()->addDays(7)->setTime(20, 0),
                    'dropoff_address' => 'Via del Corso 300, Roma RM',
                    'dropoff_latitude' => 41.9028,
                    'dropoff_longitude' => 12.4784,
                    'vehicle_return_datetime' => Carbon::now()->addDays(7)->setTime(20, 30),
                    'status_id' => $statuses->where('name', 'preventivo')->first()->id,
                    'service_price' => 350.00,
                    'driver_compensation' => 150.00,
                    'intermediary_commission' => 0,
                    'expenses' => 20.00,
                    'notes' => 'Disponibilità completa per tutta la giornata',
                ]);

                if ($drivers->isNotEmpty()) {
                    $service3->drivers()->attach($drivers->first()->id);
                }
            }
        }
    }
}
