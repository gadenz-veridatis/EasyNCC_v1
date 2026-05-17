<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReportController extends Controller
{
    /**
     * Get the company_id for the current request (multi-tenancy).
     */
    protected function getCompanyId(Request $request): ?int
    {
        if ($request->user()->isSuperAdmin()) {
            return $request->filled('company_id')
                ? (int) $request->company_id
                : $request->user()->company_id;
        }

        return $request->user()->company_id;
    }

    /**
     * Driver costs synopsis.
     *
     * Columns:
     * - compenso: driver compensation (purchase, accounting_entry = driver_cost)
     * - carburante: fuel cost (purchase, accounting_entry = fuel)
     * - ricavi_servizi: total sale transactions for services assigned to the driver
     * - ricavi_carta: sale transactions with payment_type = carta_di_credito
     * - tot_collected_driver: sale transactions with status = collected_driver
     * - tot_collected_saldo_imponibile: sale, status=collected, installment=balance, service.balance_sale_type=balance_taxable
     * - tot_collected: sale transactions with status = collected
     */
    public function driverCosts(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Load settings to get accounting entry IDs
        $settings = Settings::where('company_id', $companyId)->first();
        if (!$settings) {
            return response()->json(['error' => 'Settings not configured'], 422);
        }

        $driverCostEntryId = $settings->driver_cost_accounting_entry_id;
        $fuelEntryId = $settings->fuel_accounting_entry_id;

        $rows = DB::select("
            SELECT
                u.id as driver_id,
                u.name,
                u.surname,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.accounting_entry_id = ?
                    AND at.counterpart_id = u.id
                    THEN at.amount
                END), 0) as compenso,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.accounting_entry_id = ?
                    THEN at.amount
                END), 0) as carburante,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    THEN at.amount
                END), 0) as ricavi_servizi,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.payment_type = 'carta_di_credito'
                    THEN at.amount
                END), 0) as ricavi_carta,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected_driver'
                    THEN at.amount
                END), 0) as tot_collected_driver,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected'
                    AND at.installment = 'balance'
                    AND s.balance_sale_type = 'balance_taxable'
                    THEN at.amount
                END), 0) as tot_collected_saldo_imponibile,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected'
                    THEN at.amount
                END), 0) as tot_collected
            FROM users u
            INNER JOIN service_driver sd ON sd.user_id = u.id
            INNER JOIN services s ON s.id = sd.service_id AND s.company_id = ?
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_date BETWEEN ? AND ?
            WHERE EXISTS (
                SELECT 1 FROM driver_profiles dp WHERE dp.user_id = u.id
            )
            GROUP BY u.id, u.name, u.surname
            ORDER BY u.surname, u.name
        ", [$driverCostEntryId, $fuelEntryId, $companyId, $monthStart, $monthEnd]);

        // Calculate totals
        $totals = [
            'compenso' => 0,
            'carburante' => 0,
            'ricavi_servizi' => 0,
            'ricavi_carta' => 0,
            'tot_collected_driver' => 0,
            'tot_collected_saldo_imponibile' => 0,
            'tot_collected' => 0,
        ];

        foreach ($rows as $row) {
            foreach ($totals as $key => &$val) {
                $val += (float) $row->$key;
            }
        }

        $columnDefinitions = [
            ['key' => 'compenso', 'label' => 'Compenso', 'description' => 'Importo compenso riconosciuto al driver per i servizi nel periodo'],
            ['key' => 'carburante', 'label' => 'Carburante', 'description' => 'Costi carburante dei servizi assegnati al driver nel periodo'],
            ['key' => 'ricavi_servizi', 'label' => 'Ricavi Servizi', 'description' => 'Totale movimenti di vendita dei servizi assegnati al driver'],
            ['key' => 'ricavi_carta', 'label' => 'Ricavi Carta', 'description' => 'Ricavi dei servizi con tipologia di pagamento carta di credito'],
            ['key' => 'tot_collected_driver', 'label' => 'Collected Driver', 'description' => 'Importi incassati direttamente dal driver (stato: Incassato Driver)'],
            ['key' => 'tot_collected_saldo_imponibile', 'label' => 'Collected Saldo Imp.', 'description' => 'Importi saldo incassati dove il tipo contabilizzazione del servizio è Saldo Imponibile'],
            ['key' => 'tot_collected', 'label' => 'Collected', 'description' => 'Totale importi con stato Incassato'],
        ];

        return response()->json([
            'month' => $month,
            'rows' => $rows,
            'totals' => $totals,
            'column_definitions' => $columnDefinitions,
        ]);
    }

    /**
     * Driver cost details: breakdown by service for a specific driver and month.
     */
    public function driverCostDetails(Request $request, int $driverId): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $settings = Settings::where('company_id', $companyId)->first();
        if (!$settings) {
            return response()->json(['error' => 'Settings not configured'], 422);
        }

        $driverCostEntryId = $settings->driver_cost_accounting_entry_id;
        $fuelEntryId = $settings->fuel_accounting_entry_id;

        $rows = DB::select("
            SELECT
                s.id as service_id,
                s.reference_number,
                s.pickup_datetime,
                s.pickup_address,
                s.dropoff_address,
                s.balance_sale_type,
                client.name as client_name,
                client.surname as client_surname,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.accounting_entry_id = ?
                    AND at.counterpart_id = ?
                    THEN at.amount
                END), 0) as compenso,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.accounting_entry_id = ?
                    THEN at.amount
                END), 0) as carburante,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    THEN at.amount
                END), 0) as ricavi_servizi,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.payment_type = 'carta_di_credito'
                    THEN at.amount
                END), 0) as ricavi_carta,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected_driver'
                    THEN at.amount
                END), 0) as tot_collected_driver,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected'
                    AND at.installment = 'balance'
                    AND s.balance_sale_type = 'balance_taxable'
                    THEN at.amount
                END), 0) as tot_collected_saldo_imponibile,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected'
                    THEN at.amount
                END), 0) as tot_collected
            FROM services s
            INNER JOIN service_driver sd ON sd.service_id = s.id AND sd.user_id = ?
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_date BETWEEN ? AND ?
            LEFT JOIN users client ON client.id = s.client_id
            WHERE s.company_id = ?
            GROUP BY s.id, s.reference_number, s.pickup_datetime, s.pickup_address,
                     s.dropoff_address, s.balance_sale_type, client.name, client.surname
            ORDER BY s.pickup_datetime
        ", [$driverCostEntryId, $driverId, $fuelEntryId, $driverId, $monthStart, $monthEnd, $companyId]);

        return response()->json([
            'driver_id' => $driverId,
            'month' => $month,
            'services' => $rows,
        ]);
    }

    /**
     * Client revenue synopsis.
     *
     * Columns grouped as RICAVI (acconto, saldo, totale) and INCASSI (collected, collected_driver, collected_carta, da_incassare).
     */
    public function clientRevenue(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $rows = DB::select("
            SELECT
                u.id as client_id,
                u.name,
                u.surname,
                cp.business_name,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.installment = 'deposit'
                    THEN at.amount
                END), 0) as acconto,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.installment = 'balance'
                    THEN at.amount
                END), 0) as saldo,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    THEN at.amount
                END), 0) as totale_ricavi,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected'
                    THEN at.amount
                END), 0) as collected,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected_driver'
                    THEN at.amount
                END), 0) as collected_driver,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status IN ('collected', 'collected_driver')
                    AND at.payment_type = 'carta_di_credito'
                    THEN at.amount
                END), 0) as collected_carta,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'to_collect'
                    THEN at.amount
                END), 0) as da_incassare
            FROM users u
            INNER JOIN services s ON s.client_id = u.id AND s.company_id = ?
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_date BETWEEN ? AND ?
            LEFT JOIN client_profiles cp ON cp.user_id = u.id
            GROUP BY u.id, u.name, u.surname, cp.business_name
            ORDER BY COALESCE(cp.business_name, u.surname || ' ' || u.name)
        ", [$companyId, $monthStart, $monthEnd]);

        $totals = [
            'acconto' => 0, 'saldo' => 0, 'totale_ricavi' => 0,
            'collected' => 0, 'collected_driver' => 0, 'collected_carta' => 0, 'da_incassare' => 0,
        ];

        foreach ($rows as $row) {
            foreach ($totals as $key => &$val) {
                $val += (float) $row->$key;
            }
        }

        $columnDefinitions = [
            ['key' => 'acconto', 'label' => 'Acconto', 'group' => 'revenue', 'description' => 'Totale acconti di vendita (installment: deposit)'],
            ['key' => 'saldo', 'label' => 'Saldo', 'group' => 'revenue', 'description' => 'Totale saldi di vendita (installment: balance)'],
            ['key' => 'totale_ricavi', 'label' => 'Totale Ricavi', 'group' => 'revenue', 'description' => 'Somma di tutti i movimenti di vendita nel periodo'],
            ['key' => 'collected', 'label' => 'Collected', 'group' => 'collection', 'description' => 'Importi con stato Incassato'],
            ['key' => 'collected_driver', 'label' => 'Collected Driver', 'group' => 'collection', 'description' => 'Importi incassati direttamente dal driver (stato: Incassato Driver)'],
            ['key' => 'collected_carta', 'label' => 'Collected Carta', 'group' => 'collection', 'description' => 'Importi incassati con pagamento carta di credito'],
            ['key' => 'da_incassare', 'label' => 'Da Incassare', 'group' => 'collection', 'description' => 'Importi ancora da incassare (stato: Da Incassare)'],
        ];

        return response()->json([
            'month' => $month,
            'rows' => $rows,
            'totals' => $totals,
            'column_definitions' => $columnDefinitions,
        ]);
    }

    /**
     * Client revenue details: breakdown by service for a specific client and month.
     */
    public function clientRevenueDetails(Request $request, int $clientId): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $rows = DB::select("
            SELECT
                s.id as service_id,
                s.reference_number,
                s.pickup_datetime,
                s.pickup_address,
                s.dropoff_address,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.installment = 'deposit'
                    THEN at.amount
                END), 0) as acconto,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.installment = 'balance'
                    THEN at.amount
                END), 0) as saldo,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    THEN at.amount
                END), 0) as totale_ricavi,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected'
                    THEN at.amount
                END), 0) as collected,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'collected_driver'
                    THEN at.amount
                END), 0) as collected_driver,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status IN ('collected', 'collected_driver')
                    AND at.payment_type = 'carta_di_credito'
                    THEN at.amount
                END), 0) as collected_carta,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'sale'
                    AND at.status = 'to_collect'
                    THEN at.amount
                END), 0) as da_incassare
            FROM services s
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_date BETWEEN ? AND ?
            WHERE s.company_id = ?
              AND s.client_id = ?
            GROUP BY s.id, s.reference_number, s.pickup_datetime, s.pickup_address, s.dropoff_address
            ORDER BY s.pickup_datetime
        ", [$monthStart, $monthEnd, $companyId, $clientId]);

        return response()->json([
            'client_id' => $clientId,
            'month' => $month,
            'services' => $rows,
        ]);
    }

    /**
     * Intermediary costs synopsis.
     *
     * Columns grouped as COMMISSIONI (totale) and PAGAMENTI (pagate, da_pagare, sospese).
     */
    public function intermediaryCosts(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $rows = DB::select("
            SELECT
                u.id as intermediary_id,
                u.name,
                u.surname,
                cp.business_name,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    THEN at.amount
                END), 0) as totale_commissioni,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    AND at.status = 'paid'
                    THEN at.amount
                END), 0) as pagate,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    AND at.status = 'to_pay'
                    THEN at.amount
                END), 0) as da_pagare,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    AND at.status = 'suspended'
                    THEN at.amount
                END), 0) as sospese
            FROM users u
            INNER JOIN services s ON s.intermediary_id = u.id AND s.company_id = ?
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_type = 'intermediation'
                AND at.transaction_date BETWEEN ? AND ?
            LEFT JOIN client_profiles cp ON cp.user_id = u.id
            GROUP BY u.id, u.name, u.surname, cp.business_name
            ORDER BY COALESCE(cp.business_name, u.surname || ' ' || u.name)
        ", [$companyId, $monthStart, $monthEnd]);

        $totals = [
            'totale_commissioni' => 0, 'pagate' => 0, 'da_pagare' => 0, 'sospese' => 0,
        ];

        foreach ($rows as $row) {
            foreach ($totals as $key => &$val) {
                $val += (float) $row->$key;
            }
        }

        $columnDefinitions = [
            ['key' => 'totale_commissioni', 'label' => 'Totale Commissioni', 'group' => 'commission', 'description' => 'Somma di tutte le commissioni di intermediazione nel periodo'],
            ['key' => 'pagate', 'label' => 'Pagate', 'group' => 'payment', 'description' => 'Commissioni con stato Pagato'],
            ['key' => 'da_pagare', 'label' => 'Da Pagare', 'group' => 'payment', 'description' => 'Commissioni con stato Da Pagare'],
            ['key' => 'sospese', 'label' => 'Sospese', 'group' => 'payment', 'description' => 'Commissioni con stato Sospeso'],
        ];

        return response()->json([
            'month' => $month,
            'rows' => $rows,
            'totals' => $totals,
            'column_definitions' => $columnDefinitions,
        ]);
    }

    /**
     * Intermediary cost details: breakdown by service for a specific intermediary and month.
     */
    public function intermediaryCostDetails(Request $request, int $intermediaryId): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $rows = DB::select("
            SELECT
                s.id as service_id,
                s.reference_number,
                s.pickup_datetime,
                s.pickup_address,
                s.dropoff_address,
                client.name as client_name,
                client.surname as client_surname,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    THEN at.amount
                END), 0) as totale_commissioni,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    AND at.status = 'paid'
                    THEN at.amount
                END), 0) as pagate,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    AND at.status = 'to_pay'
                    THEN at.amount
                END), 0) as da_pagare,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'intermediation'
                    AND at.status = 'suspended'
                    THEN at.amount
                END), 0) as sospese
            FROM services s
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_type = 'intermediation'
                AND at.transaction_date BETWEEN ? AND ?
            LEFT JOIN users client ON client.id = s.client_id
            WHERE s.company_id = ?
              AND s.intermediary_id = ?
            GROUP BY s.id, s.reference_number, s.pickup_datetime, s.pickup_address,
                     s.dropoff_address, client.name, client.surname
            ORDER BY s.pickup_datetime
        ", [$monthStart, $monthEnd, $companyId, $intermediaryId]);

        return response()->json([
            'intermediary_id' => $intermediaryId,
            'month' => $month,
            'services' => $rows,
        ]);
    }

    /**
     * Supplier costs synopsis.
     *
     * Columns grouped as COSTI (totale, acconto, saldo) and PAGAMENTI (pagati, da_pagare, sospesi).
     * Includes only purchase transactions with supplier-related accounting entries (excludes driver cost entry).
     */
    public function supplierCosts(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Exclude driver cost entry from supplier costs
        $settings = Settings::where('company_id', $companyId)->first();
        $driverCostEntryId = $settings ? $settings->driver_cost_accounting_entry_id : 0;

        $rows = DB::select("
            SELECT
                u.id as supplier_id,
                u.name,
                u.surname,
                cp.business_name,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    THEN at.amount
                END), 0) as totale_costi,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.installment = 'deposit'
                    THEN at.amount
                END), 0) as acconto,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.installment = 'balance'
                    THEN at.amount
                END), 0) as saldo,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.status = 'paid'
                    THEN at.amount
                END), 0) as pagati,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.status = 'to_pay'
                    THEN at.amount
                END), 0) as da_pagare,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.status = 'suspended'
                    THEN at.amount
                END), 0) as sospesi
            FROM users u
            INNER JOIN services s ON s.supplier_id = u.id AND s.company_id = ?
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_type = 'purchase'
                AND at.accounting_entry_id != ?
                AND at.transaction_date BETWEEN ? AND ?
            LEFT JOIN client_profiles cp ON cp.user_id = u.id
            GROUP BY u.id, u.name, u.surname, cp.business_name
            ORDER BY COALESCE(cp.business_name, u.surname || ' ' || u.name)
        ", [$companyId, $driverCostEntryId, $monthStart, $monthEnd]);

        $totals = [
            'totale_costi' => 0, 'acconto' => 0, 'saldo' => 0,
            'pagati' => 0, 'da_pagare' => 0, 'sospesi' => 0,
        ];

        foreach ($rows as $row) {
            foreach ($totals as $key => &$val) {
                $val += (float) $row->$key;
            }
        }

        $columnDefinitions = [
            ['key' => 'totale_costi', 'label' => 'Totale Costi', 'group' => 'cost', 'description' => 'Somma di tutti i costi fornitore nel periodo (escluso compenso driver)'],
            ['key' => 'acconto', 'label' => 'Acconto', 'group' => 'cost', 'description' => 'Acconti versati ai fornitori (installment: deposit)'],
            ['key' => 'saldo', 'label' => 'Saldo', 'group' => 'cost', 'description' => 'Saldi versati ai fornitori (installment: balance)'],
            ['key' => 'pagati', 'label' => 'Pagati', 'group' => 'payment', 'description' => 'Costi con stato Pagato'],
            ['key' => 'da_pagare', 'label' => 'Da Pagare', 'group' => 'payment', 'description' => 'Costi con stato Da Pagare'],
            ['key' => 'sospesi', 'label' => 'Sospesi', 'group' => 'payment', 'description' => 'Costi con stato Sospeso'],
        ];

        return response()->json([
            'month' => $month,
            'rows' => $rows,
            'totals' => $totals,
            'column_definitions' => $columnDefinitions,
        ]);
    }

    /**
     * Supplier cost details: breakdown by service for a specific supplier and month.
     */
    public function supplierCostDetails(Request $request, int $supplierId): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $month = $request->input('month');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $settings = Settings::where('company_id', $companyId)->first();
        $driverCostEntryId = $settings ? $settings->driver_cost_accounting_entry_id : 0;

        $rows = DB::select("
            SELECT
                s.id as service_id,
                s.reference_number,
                s.pickup_datetime,
                s.pickup_address,
                s.dropoff_address,
                client.name as client_name,
                client.surname as client_surname,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    THEN at.amount
                END), 0) as totale_costi,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.installment = 'deposit'
                    THEN at.amount
                END), 0) as acconto,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.installment = 'balance'
                    THEN at.amount
                END), 0) as saldo,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.status = 'paid'
                    THEN at.amount
                END), 0) as pagati,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.status = 'to_pay'
                    THEN at.amount
                END), 0) as da_pagare,
                COALESCE(SUM(CASE
                    WHEN at.transaction_type = 'purchase'
                    AND at.status = 'suspended'
                    THEN at.amount
                END), 0) as sospesi
            FROM services s
            INNER JOIN accounting_transactions at ON at.service_id = s.id
                AND at.deleted_at IS NULL
                AND at.transaction_type = 'purchase'
                AND at.accounting_entry_id != ?
                AND at.transaction_date BETWEEN ? AND ?
            LEFT JOIN users client ON client.id = s.client_id
            WHERE s.company_id = ?
              AND s.supplier_id = ?
            GROUP BY s.id, s.reference_number, s.pickup_datetime, s.pickup_address,
                     s.dropoff_address, client.name, client.surname
            ORDER BY s.pickup_datetime
        ", [$driverCostEntryId, $monthStart, $monthEnd, $companyId, $supplierId]);

        return response()->json([
            'supplier_id' => $supplierId,
            'month' => $month,
            'services' => $rows,
        ]);
    }

    /**
     * Trends: daily data for an entire year + KPIs + month/quarter comparisons.
     * All monetary data sourced exclusively from accounting_transactions (sale type).
     */
    public function trends(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 422);
        }

        $year = (int) $request->input('year');
        $yearStart = "$year-01-01";
        $yearEnd = "$year-12-31";

        // Daily data: all from accounting_transactions + service count from services
        $daily = DB::select("
            SELECT
                d.day,
                COALESCE(tx.revenue, 0) as revenue,
                COALESCE(tx.collected, 0) as collected,
                COALESCE(srv.services_count, 0) as services_count
            FROM (
                SELECT generate_series(?::date, ?::date, '1 day'::interval)::date as day
            ) d
            LEFT JOIN (
                SELECT
                    at.transaction_date as day,
                    SUM(at.amount) as revenue,
                    SUM(CASE WHEN at.status IN ('collected', 'collected_driver') THEN at.amount ELSE 0 END) as collected
                FROM accounting_transactions at
                INNER JOIN services s ON s.id = at.service_id AND s.company_id = ?
                WHERE at.transaction_type = 'sale'
                  AND at.transaction_date BETWEEN ? AND ?
                  AND at.deleted_at IS NULL
                GROUP BY at.transaction_date
            ) tx ON tx.day = d.day
            LEFT JOIN (
                SELECT
                    DATE(pickup_datetime) as day,
                    COUNT(*) as services_count
                FROM services
                WHERE company_id = ?
                  AND pickup_datetime BETWEEN ? AND (?::date + interval '1 day')
                  AND deleted_at IS NULL
                GROUP BY DATE(pickup_datetime)
            ) srv ON srv.day = d.day
            ORDER BY d.day
        ", [$yearStart, $yearEnd, $companyId, $yearStart, $yearEnd, $companyId, $yearStart, $yearEnd]);

        // KPIs for the full year - all monetary from accounting_transactions
        $yearKpis = DB::selectOne("
            SELECT
                COALESCE(SUM(at.amount), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN at.status IN ('collected', 'collected_driver') THEN at.amount END), 0) as collected,
                COALESCE(SUM(CASE WHEN at.status = 'to_collect' THEN at.amount END), 0) as to_collect
            FROM accounting_transactions at
            INNER JOIN services s ON s.id = at.service_id AND s.company_id = ?
            WHERE at.transaction_type = 'sale'
              AND at.transaction_date BETWEEN ? AND ?
              AND at.deleted_at IS NULL
        ", [$companyId, $yearStart, $yearEnd]);

        $servicesCount = DB::selectOne("
            SELECT COUNT(*) as cnt
            FROM services
            WHERE company_id = ?
              AND pickup_datetime BETWEEN ? AND (?::date + interval '1 day')
              AND deleted_at IS NULL
        ", [$companyId, $yearStart, $yearEnd]);

        // Month and quarter comparisons
        $today = now();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        $monthComparison = $this->getMonthComparison($companyId, $year, $currentMonth <= 1 && $year == $currentYear ? 1 : ($year == $currentYear ? $currentMonth : 12));
        $quarterComparison = $this->getQuarterComparison($companyId, $year, $year == $currentYear ? (int) ceil($currentMonth / 3) : 4);

        return response()->json([
            'year' => $year,
            'kpis' => [
                'total_revenue' => (float) $yearKpis->total_revenue,
                'services_count' => (int) $servicesCount->cnt,
                'collected' => (float) $yearKpis->collected,
                'to_collect' => (float) $yearKpis->to_collect,
            ],
            'month_comparison' => $monthComparison,
            'quarter_comparison' => $quarterComparison,
            'daily' => $daily,
        ]);
    }

    /**
     * Compare current month vs previous month.
     */
    protected function getMonthComparison(int $companyId, int $year, int $month): array
    {
        $currentStart = sprintf('%04d-%02d-01', $year, $month);
        $currentEnd = date('Y-m-t', strtotime($currentStart));

        // Previous month
        $prevDate = date('Y-m-d', strtotime("$currentStart -1 month"));
        $prevStart = date('Y-m-01', strtotime($prevDate));
        $prevEnd = date('Y-m-t', strtotime($prevDate));

        $current = $this->getPeriodStats($companyId, $currentStart, $currentEnd);
        $previous = $this->getPeriodStats($companyId, $prevStart, $prevEnd);

        return [
            'current_label' => date('F Y', strtotime($currentStart)),
            'previous_label' => date('F Y', strtotime($prevStart)),
            'current' => $current,
            'previous' => $previous,
        ];
    }

    /**
     * Compare current quarter vs previous quarter.
     */
    protected function getQuarterComparison(int $companyId, int $year, int $quarter): array
    {
        $qStartMonth = ($quarter - 1) * 3 + 1;
        $currentStart = sprintf('%04d-%02d-01', $year, $qStartMonth);
        $currentEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $qStartMonth + 2)));

        // Previous quarter
        if ($quarter === 1) {
            $prevYear = $year - 1;
            $prevQStartMonth = 10;
        } else {
            $prevYear = $year;
            $prevQStartMonth = $qStartMonth - 3;
        }
        $prevStart = sprintf('%04d-%02d-01', $prevYear, $prevQStartMonth);
        $prevEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $prevYear, $prevQStartMonth + 2)));

        $current = $this->getPeriodStats($companyId, $currentStart, $currentEnd);
        $previous = $this->getPeriodStats($companyId, $prevStart, $prevEnd);

        return [
            'current_label' => "Q$quarter $year",
            'previous_label' => $quarter === 1 ? "Q4 " . ($year - 1) : "Q" . ($quarter - 1) . " $year",
            'current' => $current,
            'previous' => $previous,
        ];
    }

    /**
     * Get aggregated stats for a date range.
     * All monetary data from accounting_transactions; service count from services.
     */
    protected function getPeriodStats(int $companyId, string $startDate, string $endDate): array
    {
        $txStats = DB::selectOne("
            SELECT
                COALESCE(SUM(at.amount), 0) as revenue,
                COALESCE(SUM(CASE WHEN at.status IN ('collected', 'collected_driver') THEN at.amount END), 0) as collected
            FROM accounting_transactions at
            INNER JOIN services s ON s.id = at.service_id AND s.company_id = ?
            WHERE at.transaction_type = 'sale'
              AND at.transaction_date BETWEEN ? AND ?
              AND at.deleted_at IS NULL
        ", [$companyId, $startDate, $endDate]);

        $servicesCount = DB::selectOne("
            SELECT COUNT(*) as cnt
            FROM services
            WHERE company_id = ?
              AND pickup_datetime BETWEEN ? AND (?::date + interval '1 day')
              AND deleted_at IS NULL
        ", [$companyId, $startDate, $endDate]);

        $revenue = (float) $txStats->revenue;
        $count = (int) $servicesCount->cnt;

        return [
            'revenue' => $revenue,
            'services_count' => $count,
            'collected' => (float) $txStats->collected,
            'ticket_medio' => $count > 0 ? round($revenue / $count, 2) : 0,
        ];
    }
}
