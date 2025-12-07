<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccountingEntry;
use App\Models\Company;

class AccountingEntrySeeder extends Seeder
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

        $accountingEntries = [
            [
                'company_id' => $company->id,
                'name' => 'Ricavi da Servizi NCC',
                'abbreviation' => 'RICAVI-NCC',
                'type' => 'credit',
                'notes' => 'Ricavi derivanti da servizi di noleggio con conducente'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Costi Carburante',
                'abbreviation' => 'COSTO-CARB',
                'type' => 'debit',
                'notes' => 'Spese per rifornimento carburante veicoli'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Costi Manutenzione Veicoli',
                'abbreviation' => 'COSTO-MAN',
                'type' => 'debit',
                'notes' => 'Spese per manutenzione ordinaria e straordinaria veicoli'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Compensi Autisti',
                'abbreviation' => 'COMP-AUT',
                'type' => 'debit',
                'notes' => 'Compensi e stipendi corrisposti agli autisti'
            ],
            [
                'company_id' => $company->id,
                'name' => 'Commissioni Intermediari',
                'abbreviation' => 'COMM-INTER',
                'type' => 'debit',
                'notes' => 'Commissioni pagate ad agenzie e intermediari'
            ],
        ];

        foreach ($accountingEntries as $entry) {
            AccountingEntry::create($entry);
        }

        $this->command->info('5 sample accounting entries created successfully!');
    }
}
