<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountingTransaction;
use App\Models\Company;
use App\Models\Service;
use App\Models\AccountingEntry;
use App\Models\User;

class AccountingTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            $this->command->warn('No company found. Skipping AccountingTransaction seeder.');
            return;
        }

        // Get sample data
        $service = Service::where('company_id', $company->id)->first();
        $accountingEntry = AccountingEntry::where('company_id', $company->id)->first();
        $supplier = User::where('company_id', $company->id)->where('role', 'fornitore')->first();
        $client = User::where('company_id', $company->id)->where('role', 'committente')->first();
        $intermediary = User::where('company_id', $company->id)->where('role', 'intermediario')->first();

        $transactions = [
            // Vendita - Acconto da incassare dal committente
            [
                'company_id' => $company->id,
                'service_id' => $service?->id,
                'transaction_date' => now()->subDays(10),
                'amount' => 500.00,
                'transaction_type' => 'sale',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'deposit',
                'counterpart_id' => $client?->id,
                'document_number' => 'FT-2024-001',
                'document_due_date' => now()->addDays(20),
                'payment_date' => null,
                'payment_type' => 'Bonifico',
                'iban' => 'IT60X0542811101000000123456',
                'status' => 'to_collect',
                'notes' => 'Acconto per servizio transfer aeroportuale',
            ],
            // Vendita - Saldo incassato dal committente
            [
                'company_id' => $company->id,
                'service_id' => $service?->id,
                'transaction_date' => now()->subDays(3),
                'amount' => 800.00,
                'transaction_type' => 'sale',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'balance',
                'counterpart_id' => $client?->id,
                'document_number' => 'FT-2024-002',
                'document_due_date' => now()->subDays(3),
                'payment_date' => now()->subDays(1),
                'payment_type' => 'Carta di Credito',
                'iban' => null,
                'status' => 'collected',
                'notes' => 'Saldo servizio completato',
            ],
            // Acquisto - Costo fornitore da pagare
            [
                'company_id' => $company->id,
                'service_id' => $service?->id,
                'transaction_date' => now()->subDays(8),
                'amount' => 350.00,
                'transaction_type' => 'purchase',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'deposit',
                'counterpart_id' => $supplier?->id,
                'document_number' => 'FORN-2024-045',
                'document_due_date' => now()->addDays(15),
                'payment_date' => null,
                'payment_type' => 'Bonifico',
                'iban' => 'IT28W8000000292100645211151',
                'status' => 'to_pay',
                'notes' => 'Acconto fornitore noleggio veicolo esterno',
            ],
            // Acquisto - Costo fornitore pagato
            [
                'company_id' => $company->id,
                'service_id' => null,
                'transaction_date' => now()->subDays(15),
                'amount' => 1200.00,
                'transaction_type' => 'purchase',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'balance',
                'counterpart_id' => $supplier?->id,
                'document_number' => 'FORN-2024-038',
                'document_due_date' => now()->subDays(15),
                'payment_date' => now()->subDays(14),
                'payment_type' => 'Bonifico',
                'iban' => 'IT28W8000000292100645211151',
                'status' => 'paid',
                'notes' => 'Saldo fornitore servizio tour giornaliero',
            ],
            // Intermediazione - Commissione intermediario da pagare
            [
                'company_id' => $company->id,
                'service_id' => $service?->id,
                'transaction_date' => now()->subDays(5),
                'amount' => 150.00,
                'transaction_type' => 'intermediation',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'balance',
                'counterpart_id' => $intermediary?->id,
                'document_number' => 'COMM-2024-012',
                'document_due_date' => now()->addDays(25),
                'payment_date' => null,
                'payment_type' => 'Bonifico',
                'iban' => 'IT75T0300203280284975661234',
                'status' => 'to_pay',
                'notes' => 'Commissione intermediario per booking',
            ],
            // Vendita - Rimborso cliente
            [
                'company_id' => $company->id,
                'service_id' => null,
                'transaction_date' => now()->subDays(2),
                'amount' => 250.00,
                'transaction_type' => 'sale',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'customer_refund',
                'counterpart_id' => $client?->id,
                'document_number' => 'NC-2024-003',
                'document_due_date' => now()->subDays(2),
                'payment_date' => now()->subDays(1),
                'payment_type' => 'Bonifico',
                'iban' => 'IT60X0542811101000000123456',
                'status' => 'collected',
                'notes' => 'Nota di credito per cancellazione servizio',
            ],
            // Acquisto - Reso fornitore
            [
                'company_id' => $company->id,
                'service_id' => null,
                'transaction_date' => now()->subDays(7),
                'amount' => 180.00,
                'transaction_type' => 'purchase',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'supplier_refund',
                'counterpart_id' => $supplier?->id,
                'document_number' => 'RESO-2024-001',
                'document_due_date' => now()->subDays(7),
                'payment_date' => null,
                'payment_type' => 'Bonifico',
                'iban' => 'IT28W8000000292100645211151',
                'status' => 'to_collect',
                'notes' => 'Rimborso da fornitore per servizio non erogato',
            ],
            // Vendita - Movimento sospeso
            [
                'company_id' => $company->id,
                'service_id' => $service?->id,
                'transaction_date' => now()->subDays(1),
                'amount' => 650.00,
                'transaction_type' => 'sale',
                'accounting_entry_id' => $accountingEntry?->id,
                'installment' => 'deposit',
                'counterpart_id' => $client?->id,
                'document_number' => 'FT-2024-005',
                'document_due_date' => now()->addDays(30),
                'payment_date' => null,
                'payment_type' => 'Bonifico',
                'iban' => 'IT60X0542811101000000123456',
                'status' => 'suspended',
                'notes' => 'Movimento sospeso in attesa verifica committente',
            ],
        ];

        foreach ($transactions as $transaction) {
            AccountingTransaction::create($transaction);
        }

        $this->command->info('Created 8 sample accounting transactions.');
    }
}
