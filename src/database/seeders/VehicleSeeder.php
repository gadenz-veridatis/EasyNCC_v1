<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = \App\Models\Company::all();

        $vehicleData = [
            [
                'name' => 'Mercedes Classe S',
                'manufacturer' => 'Mercedes-Benz',
                'model' => 'Classe S',
                'registration_number' => 'RM-ABC-001',
                'vin' => 'WBADJ7C56FL123456',
                'year' => 2023,
                'color' => '#1a1a1a',
                'fuel_type' => 'Diesel',
                'euro_class' => 6,
            ],
            [
                'name' => 'BMW Serie 7',
                'manufacturer' => 'BMW',
                'model' => 'Serie 7',
                'registration_number' => 'RM-DEF-002',
                'vin' => 'WBADG1HD8AF654321',
                'year' => 2022,
                'color' => '#3788d8',
                'fuel_type' => 'Diesel',
                'euro_class' => 6,
            ],
            [
                'name' => 'Audi A8',
                'manufacturer' => 'Audi',
                'model' => 'A8',
                'registration_number' => 'RM-GHI-003',
                'vin' => 'WAUD3AF43MN123456',
                'year' => 2023,
                'color' => '#e74c3c',
                'fuel_type' => 'Diesel',
                'euro_class' => 6,
            ],
        ];

        foreach ($companies as $company) {
            $drivers = $company->users()
                ->where('role', 'driver')
                ->with('driverProfile')
                ->get();

            $driverIndex = 0;

            foreach ($vehicleData as $data) {
                $vehicle = \App\Models\Vehicle::create([
                    'company_id' => $company->id,
                    'name' => $data['name'],
                    'manufacturer' => $data['manufacturer'],
                    'model' => $data['model'],
                    'registration_number' => str_replace('RM', $company->name === 'NCC Roma Luxury' ? 'RM' : 'MI', $data['registration_number']),
                    'vin' => $data['vin'] . substr($company->id, -2),
                    'year' => $data['year'],
                    'color' => $data['color'],
                    'passenger_capacity' => 4,
                    'allow_overlapping' => false,
                    'purchase_date' => now()->subYears(1),
                    'fuel_type' => $data['fuel_type'],
                    'euro_class' => $data['euro_class'],
                    'notes' => 'Veicolo di alta gamma per servizi lusso',
                ]);

                // Assign vehicle to driver if available
                if ($driverIndex < $drivers->count()) {
                    $drivers[$driverIndex]->driverProfile->update([
                        'vehicle_id' => $vehicle->id,
                    ]);
                    $driverIndex++;
                }
            }
        }
    }
}
