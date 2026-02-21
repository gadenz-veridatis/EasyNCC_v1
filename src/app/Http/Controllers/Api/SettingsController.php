<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\AccountingEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Get settings for the current company
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Super admin can specify company_id
        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $companyId = $request->company_id;
        } else {
            $companyId = $user->company_id;
        }

        $settings = Settings::with([
            'company',
            'depositAccountingEntry',
            'balanceAccountingEntry',
            'defaultSupplier',
            'commissionAccountingEntry',
            'fuelAccountingEntry',
            'tollAccountingEntry',
            'parkingAccountingEntry',
            'otherVehicleAccountingEntry',
            'driverCostAccountingEntry',
            'colleagueCostAccountingEntry',
            'experienceAccountingEntry',
        ])
        ->where('company_id', $companyId)
        ->first();

        // Se non esistono settings, restituisci valori di default
        if (!$settings) {
            return response()->json([
                'data' => [
                    'company_id' => $companyId,
                    'deposit_percentage' => 30.00,
                    'card_fees_percentage' => 5.00,
                    'deposit_accounting_entry_id' => null,
                    'deposit_reason' => null,
                    'balance_accounting_entry_id' => null,
                    'balance_reason' => null,
                    'activity_confirmation_text' => null,
                    'activity_confirmation_role' => null,
                    'default_supplier_id' => null,
                    'commission_accounting_entry_id' => null,
                    'commission_reason' => null,
                    'fuel_accounting_entry_id' => null,
                    'fuel_reason' => null,
                    'toll_accounting_entry_id' => null,
                    'toll_reason' => null,
                    'parking_accounting_entry_id' => null,
                    'parking_reason' => null,
                    'other_vehicle_accounting_entry_id' => null,
                    'other_vehicle_reason' => null,
                    'driver_cost_accounting_entry_id' => null,
                    'driver_cost_reason' => null,
                    'colleague_cost_accounting_entry_id' => null,
                    'colleague_cost_reason' => null,
                    'experience_accounting_entry_id' => null,
                    'experience_reason' => null,
                ]
            ]);
        }

        return response()->json([
            'data' => $settings
        ]);
    }

    /**
     * Update or create settings for the company
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'deposit_percentage' => 'required|numeric|min:0|max:100',
            'card_fees_percentage' => 'required|numeric|min:0|max:100',
            'deposit_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'deposit_reason' => 'nullable|string|max:255',
            'balance_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'balance_reason' => 'nullable|string|max:255',
            'activity_confirmation_text' => 'nullable|string',
            'activity_confirmation_role' => 'nullable|string|in:super-admin,admin,operator,driver,collaboratore,contabilita',
            'default_supplier_id' => 'nullable|exists:users,id',
            'commission_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'commission_reason' => 'nullable|string|max:255',
            'fuel_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'fuel_reason' => 'nullable|string|max:255',
            'toll_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'toll_reason' => 'nullable|string|max:255',
            'parking_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'parking_reason' => 'nullable|string|max:255',
            'other_vehicle_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'other_vehicle_reason' => 'nullable|string|max:255',
            'driver_cost_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'driver_cost_reason' => 'nullable|string|max:255',
            'colleague_cost_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'colleague_cost_reason' => 'nullable|string|max:255',
            'experience_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'experience_reason' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // Determina company_id
        if ($user->role === 'super-admin' && isset($validated['company_id'])) {
            $companyId = $validated['company_id'];
        } else {
            $companyId = $user->company_id;
        }

        // Cerca o crea settings
        $settings = Settings::updateOrCreate(
            ['company_id' => $companyId],
            [
                'deposit_percentage' => $validated['deposit_percentage'],
                'card_fees_percentage' => $validated['card_fees_percentage'],
                'deposit_accounting_entry_id' => $validated['deposit_accounting_entry_id'] ?? null,
                'deposit_reason' => $validated['deposit_reason'] ?? null,
                'balance_accounting_entry_id' => $validated['balance_accounting_entry_id'] ?? null,
                'balance_reason' => $validated['balance_reason'] ?? null,
                'activity_confirmation_text' => $validated['activity_confirmation_text'] ?? null,
                'activity_confirmation_role' => $validated['activity_confirmation_role'] ?? null,
                'default_supplier_id' => $validated['default_supplier_id'] ?? null,
                'commission_accounting_entry_id' => $validated['commission_accounting_entry_id'] ?? null,
                'commission_reason' => $validated['commission_reason'] ?? null,
                'fuel_accounting_entry_id' => $validated['fuel_accounting_entry_id'] ?? null,
                'fuel_reason' => $validated['fuel_reason'] ?? null,
                'toll_accounting_entry_id' => $validated['toll_accounting_entry_id'] ?? null,
                'toll_reason' => $validated['toll_reason'] ?? null,
                'parking_accounting_entry_id' => $validated['parking_accounting_entry_id'] ?? null,
                'parking_reason' => $validated['parking_reason'] ?? null,
                'other_vehicle_accounting_entry_id' => $validated['other_vehicle_accounting_entry_id'] ?? null,
                'other_vehicle_reason' => $validated['other_vehicle_reason'] ?? null,
                'driver_cost_accounting_entry_id' => $validated['driver_cost_accounting_entry_id'] ?? null,
                'driver_cost_reason' => $validated['driver_cost_reason'] ?? null,
                'colleague_cost_accounting_entry_id' => $validated['colleague_cost_accounting_entry_id'] ?? null,
                'colleague_cost_reason' => $validated['colleague_cost_reason'] ?? null,
                'experience_accounting_entry_id' => $validated['experience_accounting_entry_id'] ?? null,
                'experience_reason' => $validated['experience_reason'] ?? null,
            ]
        );

        $settings->load([
            'company',
            'depositAccountingEntry',
            'balanceAccountingEntry',
            'defaultSupplier',
            'commissionAccountingEntry',
            'fuelAccountingEntry',
            'tollAccountingEntry',
            'parkingAccountingEntry',
            'otherVehicleAccountingEntry',
            'driverCostAccountingEntry',
            'colleagueCostAccountingEntry',
            'experienceAccountingEntry',
        ]);

        return response()->json([
            'message' => 'Impostazioni salvate con successo',
            'data' => $settings
        ]);
    }

    /**
     * Get accounting entries for dropdowns
     */
    public function getAccountingEntries(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $companyId = $request->company_id;
        } else {
            $companyId = $user->company_id;
        }

        $entries = AccountingEntry::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'abbreviation']);

        return response()->json([
            'data' => $entries
        ]);
    }
}
