<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PricingDestination;
use App\Models\Company;

class PricingDestinationSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            ['destination' => 'Personalizzato', 'service_type' => '', 'duration_hours' => 0, 'mileage_km' => 0, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Chianti Classico', 'service_type' => 'TOUR HD', 'duration_hours' => 5, 'mileage_km' => 150, 'toll_cost' => 0, 'experience_a' => 50, 'experience_b' => 20, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Montalcino', 'service_type' => 'TOUR HD', 'duration_hours' => 5, 'mileage_km' => 150, 'toll_cost' => 0, 'experience_a' => 50, 'experience_b' => 20, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Monteriggioni-SanGi', 'service_type' => 'TOUR HD', 'duration_hours' => 5, 'mileage_km' => 150, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Modena', 'service_type' => 'TOUR FD+', 'duration_hours' => 10, 'mileage_km' => 400, 'toll_cost' => 20, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Bolgheri', 'service_type' => 'TOUR FD+', 'duration_hours' => 10, 'mileage_km' => 400, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Suvereto', 'service_type' => 'TOUR FD+', 'duration_hours' => 10, 'mileage_km' => 400, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Chianti Classico', 'service_type' => 'TOUR FD', 'duration_hours' => 8, 'mileage_km' => 250, 'toll_cost' => 0, 'experience_a' => 50, 'experience_b' => 20, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Montalcino-Pienza', 'service_type' => 'TOUR FD', 'duration_hours' => 9, 'mileage_km' => 250, 'toll_cost' => 0, 'experience_a' => 50, 'experience_b' => 20, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Pisa-Lucca', 'service_type' => 'TOUR FD', 'duration_hours' => 9, 'mileage_km' => 300, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Monteriggioni-SanGi-Volterra', 'service_type' => 'TOUR FD', 'duration_hours' => 9, 'mileage_km' => 250, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Assisi', 'service_type' => 'TOUR FD', 'duration_hours' => 9, 'mileage_km' => 300, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Siena', 'service_type' => 'TRF', 'duration_hours' => 1, 'mileage_km' => 10, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Siena dintorni', 'service_type' => 'TRF', 'duration_hours' => 2, 'mileage_km' => 30, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Arezzo', 'service_type' => 'TRF', 'duration_hours' => 3, 'mileage_km' => 180, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Chiusi', 'service_type' => 'TRF', 'duration_hours' => 3, 'mileage_km' => 180, 'toll_cost' => 5, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Civitavecchia', 'service_type' => 'TRF', 'duration_hours' => 5, 'mileage_km' => 370, 'toll_cost' => 5, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Cortona', 'service_type' => 'TRF', 'duration_hours' => 3, 'mileage_km' => 150, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Grosseto', 'service_type' => 'TRF', 'duration_hours' => 3, 'mileage_km' => 150, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Como', 'service_type' => 'TRF', 'duration_hours' => 10, 'mileage_km' => 840, 'toll_cost' => 65, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Bologna', 'service_type' => 'TRF', 'duration_hours' => 5, 'mileage_km' => 340, 'toll_cost' => 20, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Firenze', 'service_type' => 'TRF', 'duration_hours' => 3, 'mileage_km' => 150, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Genova', 'service_type' => 'TRF', 'duration_hours' => 7, 'mileage_km' => 600, 'toll_cost' => 50, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'La Spezia', 'service_type' => 'TRF', 'duration_hours' => 5, 'mileage_km' => 420, 'toll_cost' => 30, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Livorno', 'service_type' => 'TRF', 'duration_hours' => 4, 'mileage_km' => 260, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Lucca', 'service_type' => 'TRF', 'duration_hours' => 4, 'mileage_km' => 280, 'toll_cost' => 12, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Milano', 'service_type' => 'TRF', 'duration_hours' => 8, 'mileage_km' => 740, 'toll_cost' => 50, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Montalcino', 'service_type' => 'TRF', 'duration_hours' => 2, 'mileage_km' => 90, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Montecatini', 'service_type' => 'TRF', 'duration_hours' => 3, 'mileage_km' => 230, 'toll_cost' => 10, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Montepulciano', 'service_type' => 'TRF', 'duration_hours' => 2, 'mileage_km' => 130, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Monterosso', 'service_type' => 'TRF', 'duration_hours' => 7, 'mileage_km' => 500, 'toll_cost' => 30, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'MXP', 'service_type' => 'TRF', 'duration_hours' => 9, 'mileage_km' => 830, 'toll_cost' => 60, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Napoli', 'service_type' => 'TRF', 'duration_hours' => 9, 'mileage_km' => 860, 'toll_cost' => 60, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Orvieto', 'service_type' => 'TRF', 'duration_hours' => 4, 'mileage_km' => 250, 'toll_cost' => 10, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Perugia', 'service_type' => 'TRF', 'duration_hours' => 4, 'mileage_km' => 250, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Pisa', 'service_type' => 'TRF', 'duration_hours' => 4, 'mileage_km' => 250, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Porto Ercole', 'service_type' => 'TRF', 'duration_hours' => 2, 'mileage_km' => 250, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Roma', 'service_type' => 'TRF', 'duration_hours' => 6, 'mileage_km' => 500, 'toll_cost' => 27, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'San Gimignano', 'service_type' => 'TRF', 'duration_hours' => 2, 'mileage_km' => 80, 'toll_cost' => 0, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Sirmione', 'service_type' => 'TRF', 'duration_hours' => 8, 'mileage_km' => 620, 'toll_cost' => 40, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Sorrento', 'service_type' => 'TRF', 'duration_hours' => 10, 'mileage_km' => 940, 'toll_cost' => 65, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
            ['destination' => 'Venezia', 'service_type' => 'TRF', 'duration_hours' => 8, 'mileage_km' => 640, 'toll_cost' => 45, 'experience_a' => 0, 'experience_b' => 0, 'experience_c' => 0, 'experience_d' => 0],
        ];

        // Seed for each company
        $companies = Company::all();
        foreach ($companies as $company) {
            foreach ($destinations as $index => $dest) {
                $name = $dest['service_type']
                    ? $dest['destination'] . ' - ' . $dest['service_type']
                    : $dest['destination'];

                PricingDestination::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'destination' => $dest['destination'],
                        'service_type' => $dest['service_type'],
                    ],
                    array_merge($dest, [
                        'company_id' => $company->id,
                        'name' => $name,
                        'sort_order' => $index,
                    ])
                );
            }
        }
    }
}
