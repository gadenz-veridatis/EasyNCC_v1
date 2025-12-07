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
                'brand' => 'Mercedes-Benz',
                'model' => 'Classe S',
                'license_plate' => 'RM-ABC-001',
                'passenger_capacity' => 4,
                'ncc_license_number' => 'NCC001',
                'license_city' => 'Roma',
            ],
            [
                'brand' => 'BMW',
                'model' => 'Serie 7',
                'license_plate' => 'RM-DEF-002',
                'passenger_capacity' => 4,
                'ncc_license_number' => 'NCC002',
                'license_city' => 'Roma',
            ],
            [
                'brand' => 'Audi',
                'model' => 'A8',
                'license_plate' => 'RM-GHI-003',
                'passenger_capacity' => 4,
                'ncc_license_number' => 'NCC003',
                'license_city' => 'Roma',
            ],
        ];

        foreach ($companies as $company) {
            $drivers = $company->users()
                ->where('role', 'driver')
                ->with('driverProfile')
                ->get();

            $driverIndex = 0;

            foreach ($vehicleData as $index => $data) {
                $vehicle = \App\Models\Vehicle::create([
                    'company_id' => $company->id,
                    'license_plate' => str_replace('RM', $company->name === 'NCC Roma Luxury' ? 'RM' : 'MI', $data['license_plate']),
                    'brand' => $data['brand'],
                    'model' => $data['model'],
                    'passenger_capacity' => $data['passenger_capacity'],
                    'purchase_date' => now()->subYears(1),
                    'ncc_license_number' => $data['ncc_license_number'],
                    'license_city' => $data['license_city'],
                    'allow_overlapping' => false,
                    'status' => 'in_service',
                    'notes' => 'Veicolo di alta gamma per servizi lusso',
                ]);

                // Assign vehicle to driver if available
                if ($driverIndex < $drivers->count() && $drivers[$driverIndex]->driverProfile) {
                    $drivers[$driverIndex]->driverProfile->update([
                        'assigned_vehicle_id' => $vehicle->id,
                    ]);
                    $driverIndex++;
                }
            }
        }
    }
}
