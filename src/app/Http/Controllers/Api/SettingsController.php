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
            'defaultSupplier'
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
            ]
        );

        $settings->load([
            'company',
            'depositAccountingEntry',
            'balanceAccountingEntry',
            'defaultSupplier'
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
