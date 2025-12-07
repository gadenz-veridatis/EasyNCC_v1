<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceType;
use App\Models\Company;

class ServiceTypeSeeder extends Seeder
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

        $serviceTypes = [
            [
                'company_id' => $company->id,
                'name' => 'Transfer Aeroportuale',
                'abbreviation' => 'AIRPORT',
                'notes' => 'Servizio di trasferimento da/per aeroporti nazionali e internazionali'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Tour Giornaliero',
                'abbreviation' => 'TOUR',
                'notes' => 'Servizio di tour completo con conducente per intera giornata'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Disposizione Oraria',
                'abbreviation' => 'DISPO',
                'notes' => 'Servizio a disposizione con tariffa oraria'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Trasferimento Stazione',
                'abbreviation' => 'STATION',
                'notes' => 'Servizio di trasferimento da/per stazioni ferroviarie'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Business Meeting',
                'abbreviation' => 'MEETING',
                'notes' => 'Servizio dedicato a meeting aziendali e appuntamenti business'
            ],
        ];

        foreach ($serviceTypes as $type) {
            ServiceType::create($type);
        }

        $this->command->info('5 sample service types created successfully!');
    }
}
