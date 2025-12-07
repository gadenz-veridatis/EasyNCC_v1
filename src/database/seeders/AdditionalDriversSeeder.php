<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdditionalDriversSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            // Create Driver 3 - Luca Ferrari (Green color)
            $driver3 = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Luca',
                'surname' => 'Ferrari',
                'nickname' => 'luca_ferrari_' . $company->id,
                'email' => 'luca.ferrari' . $company->id . '@easyncc.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Driver, 20',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => '+39 06 7777777',
                'role' => 'driver',
                'is_active' => true,
            ]);

            \App\Models\DriverProfile::create([
                'user_id' => $driver3->id,
                'assigned_vehicle_id' => null,
                'birth_date' => '1988-11-10',
                'hourly_rate' => 33.00,
                'allow_overlapping' => false,
                'color' => '#28a745', // Green
                'notes' => 'Driver puntuale e professionale',
            ]);

            // Create Driver 4 - Andrea Romano (Orange color)
            $driver4 = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Andrea',
                'surname' => 'Romano',
                'nickname' => 'andrea_romano_' . $company->id,
                'email' => 'andrea.romano' . $company->id . '@easyncc.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Driver, 25',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => '+39 06 8888888',
                'role' => 'driver',
                'is_active' => true,
            ]);

            \App\Models\DriverProfile::create([
                'user_id' => $driver4->id,
                'assigned_vehicle_id' => null,
                'birth_date' => '1992-05-18',
                'hourly_rate' => 30.00,
                'allow_overlapping' => true,
                'color' => '#fd7e14', // Orange
                'notes' => 'Driver giovane e dinamico',
            ]);

            // Create Driver 5 - Stefano Colombo (Purple color)
            $driver5 = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Stefano',
                'surname' => 'Colombo',
                'nickname' => 'stefano_colombo_' . $company->id,
                'email' => 'stefano.colombo' . $company->id . '@easyncc.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Driver, 30',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => '+39 06 9999999',
                'role' => 'driver',
                'is_active' => true,
            ]);

            \App\Models\DriverProfile::create([
                'user_id' => $driver5->id,
                'assigned_vehicle_id' => null,
                'birth_date' => '1987-09-25',
                'hourly_rate' => 34.00,
                'allow_overlapping' => false,
                'color' => '#6f42c1', // Purple
                'notes' => 'Driver con esperienza internazionale',
            ]);
        }
    }
}
