<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super-admin user (independent from company)
        $superAdmin = \App\Models\User::create([
            'name' => 'Admin',
            'surname' => 'System',
            'nickname' => 'admin',
            'email' => 'admin@easyncc.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'address' => 'Via Amministrazione, 1',
            'postal_code' => '00100',
            'city' => 'Roma',
            'province' => 'RM',
            'country' => 'Italia',
            'phone' => '+39 06 1111111',
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        // Get all companies
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            // Create admin for company
            $adminEmail = strtolower(str_replace([' ', '.'], '', $company->name)) . '@easyncc.com';

            $admin = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Admin',
                'surname' => $company->name,
                'nickname' => 'admin_' . $company->id,
                'email' => $adminEmail,
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Amministrazione, 1',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => $company->phone,
                'role' => 'admin',
                'is_active' => true,
            ]);

            // Create operator
            $operator = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Operatore',
                'surname' => 'Centrale',
                'nickname' => 'operator_' . $company->id,
                'email' => 'operator' . $company->id . '@easyncc.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Operativa, 10',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => '+39 06 5555555',
                'role' => 'operator',
                'is_active' => true,
            ]);

            // Create 2 drivers with profiles
            $driver1 = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Marco',
                'surname' => 'Rossi',
                'nickname' => 'marco_rossi_' . $company->id,
                'email' => 'marco.rossi' . $company->id . '@easyncc.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Driver, 5',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => '+39 06 3333333',
                'role' => 'driver',
                'is_active' => true,
            ]);

            \App\Models\DriverProfile::create([
                'user_id' => $driver1->id,
                'vehicle_id' => null, // Will be assigned in VehicleSeeder
                'birth_date' => '1985-03-15',
                'hourly_rate' => 35.00,
                'allow_overlapping' => false,
                'license_number' => 'AB123456789IT',
                'license_expiry_date' => '2028-03-15',
                'notes' => 'Driver esperto con ottima esperienza nel lusso',
            ]);

            $driver2 = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Giovanni',
                'surname' => 'Bianchi',
                'nickname' => 'giovanni_bianchi_' . $company->id,
                'email' => 'giovanni.bianchi' . $company->id . '@easyncc.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Driver, 12',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => '+39 06 4444444',
                'role' => 'driver',
                'is_active' => true,
            ]);

            \App\Models\DriverProfile::create([
                'user_id' => $driver2->id,
                'vehicle_id' => null, // Will be assigned in VehicleSeeder
                'birth_date' => '1990-07-22',
                'hourly_rate' => 32.00,
                'allow_overlapping' => true,
                'license_number' => 'CD987654321IT',
                'license_expiry_date' => '2026-07-22',
                'notes' => 'Driver affidabile e cortese',
            ]);

            // Create client
            $client = \App\Models\User::create([
                'company_id' => $company->id,
                'name' => 'Paolo',
                'surname' => 'Verdi',
                'nickname' => 'paolo_verdi_' . $company->id,
                'email' => 'paolo.verdi' . $company->id . '@easyncc.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'address' => 'Via Cliente, 100',
                'postal_code' => '00100',
                'city' => ($company->name === 'NCC Roma Luxury') ? 'Roma' : 'Milano',
                'province' => 'RM',
                'country' => 'Italia',
                'phone' => '+39 06 2222222',
                'role' => 'committente',
                'is_active' => true,
            ]);

            \App\Models\ClientProfile::create([
                'user_id' => $client->id,
                'commission' => 10.00,
                'notes' => 'Cliente fedele con prenotazioni regolari',
            ]);
        }
    }
}
