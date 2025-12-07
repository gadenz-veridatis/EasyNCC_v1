<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Service;
use App\Models\ActivityType;
use App\Models\User;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first company
        $company = Company::first();

        if (!$company) {
            $this->command->error('No company found. Please run CompanySeeder first.');
            return;
        }

        // Get first service (optional)
        $service = Service::where('company_id', $company->id)->first();

        // Get first activity type (optional)
        $activityType = ActivityType::where('company_id', $company->id)->first();

        // Get first supplier (optional) - user with clientProfile where is_fornitore = true
        $supplier = User::where('company_id', $company->id)
            ->whereHas('clientProfile', function($query) {
                $query->where('is_fornitore', true);
            })
            ->first();

        $activities = [
            [
                'company_id' => $company->id,
                'service_id' => $service?->id,
                'activity_type_id' => $activityType?->id,
                'name' => 'Visita guidata Colosseo',
                'supplier_id' => $supplier?->id,
                'start_time' => now()->addDays(5)->setTime(9, 0),
                'end_time' => now()->addDays(5)->setTime(12, 0),
                'cost' => 150.00,
                'cost_per_person' => 25.00,
                'payment_type' => 'CLIENTE',
                'notes' => 'Ingresso incluso, guida certificata in italiano e inglese',
            ],
            [
                'company_id' => $company->id,
                'service_id' => $service?->id,
                'activity_type_id' => $activityType?->id,
                'name' => 'Pranzo ristorante La Pergola',
                'supplier_id' => $supplier?->id,
                'start_time' => now()->addDays(5)->setTime(13, 0),
                'end_time' => now()->addDays(5)->setTime(15, 30),
                'cost' => 480.00,
                'cost_per_person' => 80.00,
                'payment_type' => 'INCLUSO',
                'notes' => 'Menu degustazione 5 portate, bevande incluse',
            ],
            [
                'company_id' => $company->id,
                'service_id' => null,
                'activity_type_id' => $activityType?->id,
                'name' => 'Tour enogastronomico Toscana',
                'supplier_id' => $supplier?->id,
                'start_time' => now()->addDays(10)->setTime(10, 0),
                'end_time' => now()->addDays(10)->setTime(18, 0),
                'cost' => null,
                'cost_per_person' => null,
                'payment_type' => null,
                'notes' => 'Visita 3 cantine con degustazione, pranzo tipico incluso',
            ],
            [
                'company_id' => $company->id,
                'service_id' => null,
                'activity_type_id' => $activityType?->id,
                'name' => 'Biglietti Musei Vaticani',
                'supplier_id' => null,
                'start_time' => now()->addDays(7)->setTime(14, 0),
                'end_time' => now()->addDays(7)->setTime(17, 0),
                'cost' => 120.00,
                'cost_per_person' => 20.00,
                'payment_type' => 'AGENZIA',
                'notes' => 'Skip the line, include Cappella Sistina',
            ],
            [
                'company_id' => $company->id,
                'service_id' => null,
                'activity_type_id' => null,
                'name' => 'Esperienza Opera Lirica',
                'supplier_id' => $supplier?->id,
                'start_time' => now()->addDays(15)->setTime(20, 0),
                'end_time' => now()->addDays(15)->setTime(23, 0),
                'cost' => 300.00,
                'cost_per_person' => 50.00,
                'payment_type' => 'NESSUNO',
                'notes' => 'Teatro dell\'Opera, posti platea, aperitivo pre-spettacolo',
            ],
        ];

        foreach ($activities as $activityData) {
            Activity::create($activityData);
        }

        $this->command->info('5 sample activities created successfully!');
    }
}
