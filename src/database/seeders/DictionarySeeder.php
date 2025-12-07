<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DressCode;
use App\Models\ServiceStatus;
use App\Models\PaymentType;
// use App\Models\LuggageType; // Not implemented yet
use App\Models\DriverAttachmentType;
use App\Models\VehicleAttachmentType;
use App\Models\Ztl;
use App\Models\Company;

class DictionarySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            DressCode::create(['company_id' => $company->id, 'name' => 'Business Casual']);
            DressCode::create(['company_id' => $company->id, 'name' => 'Abito Elegante']);
            DressCode::create(['company_id' => $company->id, 'name' => 'Casual']);

            ServiceStatus::create(['company_id' => $company->id, 'name' => 'preventivo']);
            ServiceStatus::create(['company_id' => $company->id, 'name' => 'confermato']);
            ServiceStatus::create(['company_id' => $company->id, 'name' => 'in corso']);
            ServiceStatus::create(['company_id' => $company->id, 'name' => 'completato']);
            ServiceStatus::create(['company_id' => $company->id, 'name' => 'cancellato']);
            ServiceStatus::create(['company_id' => $company->id, 'name' => 'no-show']);

            PaymentType::create(['company_id' => $company->id, 'name' => 'Bonifico']);
            PaymentType::create(['company_id' => $company->id, 'name' => 'Carta di Credito']);
            PaymentType::create(['company_id' => $company->id, 'name' => 'Contanti']);

            // LuggageType table not implemented yet
            // LuggageType::create(['company_id' => $company->id, 'name' => 'Bagagli', 'code' => 'BAG']);
            // LuggageType::create(['company_id' => $company->id, 'name' => 'Disposizione', 'code' => 'DISPO']);

            DriverAttachmentType::create(['company_id' => $company->id, 'name' => 'Antincendio']);
            DriverAttachmentType::create(['company_id' => $company->id, 'name' => 'Codice Fiscale']);
            DriverAttachmentType::create(['company_id' => $company->id, 'name' => 'Patente']);

            VehicleAttachmentType::create(['company_id' => $company->id, 'name' => 'Assicurazione']);
            VehicleAttachmentType::create(['company_id' => $company->id, 'name' => 'Bollo']);
            VehicleAttachmentType::create(['company_id' => $company->id, 'name' => 'Revisione']);

            Ztl::create(['company_id' => $company->id, 'city' => 'Roma Centro', 'duration' => 60, 'cost' => 5.00]);
            Ztl::create(['company_id' => $company->id, 'city' => 'Milano Area C', 'duration' => 120, 'cost' => 7.50]);
        }
    }
}
