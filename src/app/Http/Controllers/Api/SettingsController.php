<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Settings;
use App\Models\AccountingEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        $settings = Settings::where('company_id', $companyId)->first();

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
                    'activity_confirmation_user_ids' => null,
                    'activity_confirmation_default_user_id' => null,
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
                    'handling_fees_accounting_entry_id' => null,
                    'handling_fees_reason' => null,
                    'card_fees_accounting_entry_id' => null,
                    'card_fees_reason' => null,
                    'extra_revenue_accounting_entry_id' => null,
                    'extra_revenue_reason' => null,
                    'telegram_trigger_status_id' => null,
                    'telegram_accepted_status_id' => null,
                    'telegram_closed_ok_status_id' => null,
                    'telegram_closed_ko_status_id' => null,
                    'telegram_collected_status_id' => null,
                    'telegram_location_status_ids' => null,
                    'service_cancel_status_ids' => null,
                    'email_accepted_status_id' => null,
                    'email_closed_status_id' => null,
                    'email_notification_address' => null,
                    'email_assignment_template_id' => null,
                    'email_closure_template_id' => null,
                    'email_gmail_account_id' => null,
                    'email_token_expiry_days' => 7,
                    'aviationstack_api_key' => null,
                    'flight_tracking_enabled' => false,
                    'pricing_markups' => null,
                    'pricing_vehicle_costs' => null,
                    'pricing_vehicle_assumptions' => null,
                    'pricing_annual_expenses' => null,
                    'pricing_season_service' => null,
                    'pricing_vehicle_service' => null,
                    'pricing_season_experience' => null,
                    'pricing_vehicle_experience' => null,
                    'pricing_attenuation_transport' => null,
                    'pricing_attenuation_driver' => null,
                    'pricing_extension' => null,
                    'pricing_depreciation' => null,
                    'pricing_toll' => null,
                    'gmail_label_richieste' => null,
                    'gmail_subject_tag' => null,
                    'gmail_polling_interval' => 60,
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
            'activity_confirmation_user_ids' => 'nullable|array',
            'activity_confirmation_user_ids.*' => 'exists:users,id',
            'activity_confirmation_default_user_id' => 'nullable|exists:users,id',
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
            'handling_fees_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'handling_fees_reason' => 'nullable|string|max:255',
            'card_fees_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'card_fees_reason' => 'nullable|string|max:255',
            'extra_revenue_accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'extra_revenue_reason' => 'nullable|string|max:255',
            'telegram_trigger_status_id' => 'nullable|exists:service_statuses,id',
            'telegram_accepted_status_id' => 'nullable|exists:service_statuses,id',
            'telegram_closed_ok_status_id' => 'nullable|exists:service_statuses,id',
            'telegram_closed_ko_status_id' => 'nullable|exists:service_statuses,id',
            'telegram_collected_status_id' => 'nullable|exists:service_statuses,id',
            'telegram_location_status_ids' => 'nullable|array',
            'telegram_location_status_ids.*' => 'exists:service_statuses,id',
            'service_cancel_status_ids' => 'nullable|array',
            'service_cancel_status_ids.*' => 'exists:service_statuses,id',
            'email_accepted_status_id' => 'nullable|exists:service_statuses,id',
            'email_closed_status_id' => 'nullable|exists:service_statuses,id',
            'email_notification_address' => 'nullable|email|max:255',
            'email_assignment_template_id' => 'nullable|exists:quote_email_templates,id',
            'email_closure_template_id' => 'nullable|exists:quote_email_templates,id',
            'email_gmail_account_id' => 'nullable|exists:gmail_accounts,id',
            'email_token_expiry_days' => 'nullable|integer|min:1|max:90',
            'aviationstack_api_key' => 'nullable|string|max:255',
            'flight_tracking_enabled' => 'nullable|boolean',
            'pricing_markups' => 'nullable|array',
            'pricing_vehicle_costs' => 'nullable|array',
            'pricing_vehicle_assumptions' => 'nullable|array',
            'pricing_annual_expenses' => 'nullable|array',
            'pricing_season_service' => 'nullable|array',
            'pricing_vehicle_service' => 'nullable|array',
            'pricing_season_experience' => 'nullable|array',
            'pricing_vehicle_experience' => 'nullable|array',
            'pricing_attenuation_transport' => 'nullable|array',
            'pricing_attenuation_driver' => 'nullable|array',
            'pricing_extension' => 'nullable|array',
            'pricing_depreciation' => 'nullable|array',
            'pricing_toll' => 'nullable|array',
            'gmail_label_richieste' => 'nullable|string|max:255',
            'gmail_subject_tag' => 'nullable|string|max:100',
            'gmail_polling_interval' => 'nullable|integer|min:5|max:1440',
        ]);

        $user = Auth::user();

        // Determina company_id
        if ($user->role === 'super-admin' && isset($validated['company_id'])) {
            $companyId = $validated['company_id'];
        } else {
            $companyId = $user->company_id;
        }

        // Recupera settings esistenti per preservare pricing config non inviati
        $existing = Settings::where('company_id', $companyId)->first();

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
                'activity_confirmation_user_ids' => $validated['activity_confirmation_user_ids'] ?? null,
                'activity_confirmation_default_user_id' => $validated['activity_confirmation_default_user_id'] ?? null,
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
                'handling_fees_accounting_entry_id' => $validated['handling_fees_accounting_entry_id'] ?? null,
                'handling_fees_reason' => $validated['handling_fees_reason'] ?? null,
                'card_fees_accounting_entry_id' => $validated['card_fees_accounting_entry_id'] ?? null,
                'card_fees_reason' => $validated['card_fees_reason'] ?? null,
                'extra_revenue_accounting_entry_id' => $validated['extra_revenue_accounting_entry_id'] ?? null,
                'extra_revenue_reason' => $validated['extra_revenue_reason'] ?? null,
                'telegram_trigger_status_id' => $validated['telegram_trigger_status_id'] ?? null,
                'telegram_accepted_status_id' => $validated['telegram_accepted_status_id'] ?? null,
                'telegram_closed_ok_status_id' => $validated['telegram_closed_ok_status_id'] ?? null,
                'telegram_closed_ko_status_id' => $validated['telegram_closed_ko_status_id'] ?? null,
                'telegram_collected_status_id' => $validated['telegram_collected_status_id'] ?? null,
                'telegram_location_status_ids' => $validated['telegram_location_status_ids'] ?? null,
                'service_cancel_status_ids' => $validated['service_cancel_status_ids'] ?? null,
                'email_accepted_status_id' => $validated['email_accepted_status_id'] ?? null,
                'email_closed_status_id' => $validated['email_closed_status_id'] ?? null,
                'email_notification_address' => $validated['email_notification_address'] ?? null,
                'email_assignment_template_id' => $validated['email_assignment_template_id'] ?? null,
                'email_closure_template_id' => $validated['email_closure_template_id'] ?? null,
                'email_gmail_account_id' => $validated['email_gmail_account_id'] ?? null,
                'email_token_expiry_days' => $validated['email_token_expiry_days'] ?? 7,
                'aviationstack_api_key' => $validated['aviationstack_api_key'] ?? null,
                'flight_tracking_enabled' => $validated['flight_tracking_enabled'] ?? false,
                'pricing_markups' => $validated['pricing_markups'] ?? $existing->pricing_markups ?? null,
                'pricing_vehicle_costs' => $validated['pricing_vehicle_costs'] ?? $existing->pricing_vehicle_costs ?? null,
                'pricing_vehicle_assumptions' => $validated['pricing_vehicle_assumptions'] ?? $existing->pricing_vehicle_assumptions ?? null,
                'pricing_annual_expenses' => $validated['pricing_annual_expenses'] ?? $existing->pricing_annual_expenses ?? null,
                'pricing_season_service' => $validated['pricing_season_service'] ?? $existing->pricing_season_service ?? null,
                'pricing_vehicle_service' => $validated['pricing_vehicle_service'] ?? $existing->pricing_vehicle_service ?? null,
                'pricing_season_experience' => $validated['pricing_season_experience'] ?? $existing->pricing_season_experience ?? null,
                'pricing_vehicle_experience' => $validated['pricing_vehicle_experience'] ?? $existing->pricing_vehicle_experience ?? null,
                'pricing_attenuation_transport' => $validated['pricing_attenuation_transport'] ?? $existing->pricing_attenuation_transport ?? null,
                'pricing_attenuation_driver' => $validated['pricing_attenuation_driver'] ?? $existing->pricing_attenuation_driver ?? null,
                'pricing_extension' => $validated['pricing_extension'] ?? $existing->pricing_extension ?? null,
                'pricing_depreciation' => $validated['pricing_depreciation'] ?? $existing->pricing_depreciation ?? null,
                'pricing_toll' => $validated['pricing_toll'] ?? $existing->pricing_toll ?? null,
                'gmail_label_richieste' => $validated['gmail_label_richieste'] ?? $existing->gmail_label_richieste ?? null,
                'gmail_subject_tag' => $validated['gmail_subject_tag'] ?? $existing->gmail_subject_tag ?? null,
                'gmail_polling_interval' => $validated['gmail_polling_interval'] ?? $existing->gmail_polling_interval ?? 60,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Impostazioni salvate con successo',
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

    /**
     * Get suppliers for dropdown (lightweight: only id, name, surname)
     */
    public function getSuppliers(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $companyId = $request->company_id;
        } else {
            $companyId = $user->company_id;
        }

        $suppliers = User::select('id', 'name', 'surname')
            ->where('company_id', $companyId)
            ->where('role', 'collaboratore')
            ->whereHas('clientProfile', function ($q) {
                $q->where('is_fornitore', true);
            })
            ->orderBy('surname')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $suppliers
        ]);
    }

    /**
     * Get available service statuses for settings dropdowns.
     */
    public function getServiceStatuses(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $companyId = $request->company_id;
        } else {
            $companyId = $user->company_id;
        }

        $statuses = \App\Models\ServiceStatus::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $statuses
        ]);
    }

    /**
     * Get company data for the "Dati Azienda" tab in settings.
     */
    public function getCompanyData(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $companyId = $request->company_id;
        } else {
            $companyId = $user->company_id;
        }

        $company = Company::find($companyId);

        if (!$company) {
            return response()->json(['message' => 'Azienda non trovata'], 404);
        }

        $data = $company->toArray();

        // Add full URLs for image fields
        foreach (['logo', 'stamp', 'stamp_with_signature'] as $field) {
            $data["{$field}_url"] = $company->$field
                ? Storage::disk('public')->url($company->$field)
                : null;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Update company data from the "Dati Azienda" tab in settings.
     */
    public function updateCompanyData(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $companyId = $request->company_id;
        } else {
            $companyId = $user->company_id;
        }

        $company = Company::find($companyId);

        if (!$company) {
            return response()->json(['message' => 'Azienda non trovata'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'sdi' => 'nullable|string|max:255',
            'pec' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'rea' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'stamp' => 'nullable|image|max:2048',
            'stamp_with_signature' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
            'remove_stamp' => 'nullable|boolean',
            'remove_stamp_with_signature' => 'nullable|boolean',
        ]);

        // Handle text fields
        $company->fill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'vat_number' => $validated['vat_number'] ?? null,
            'sdi' => $validated['sdi'] ?? null,
            'pec' => $validated['pec'] ?? null,
            'address' => $validated['address'] ?? null,
            'website' => $validated['website'] ?? null,
            'rea' => $validated['rea'] ?? null,
        ]);

        // Handle image uploads and removals
        foreach (['logo', 'stamp', 'stamp_with_signature'] as $field) {
            if ($request->boolean("remove_{$field}")) {
                // Remove existing file
                if ($company->$field) {
                    Storage::disk('public')->delete($company->$field);
                }
                $company->$field = null;
            } elseif ($request->hasFile($field)) {
                // Delete old file if exists
                if ($company->$field) {
                    Storage::disk('public')->delete($company->$field);
                }
                $company->$field = $request->file($field)->store("company_{$companyId}", 'public');
            }
        }

        $company->save();

        // Return updated data with URLs
        $data = $company->toArray();
        foreach (['logo', 'stamp', 'stamp_with_signature'] as $field) {
            $data["{$field}_url"] = $company->$field
                ? Storage::disk('public')->url($company->$field)
                : null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Dati aziendali salvati con successo',
            'data' => $data,
        ]);
    }

    /**
     * Get public settings (accessible to all authenticated users).
     * Returns only non-sensitive configuration needed by the frontend.
     */
    public function publicSettings(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $settings = Settings::where('company_id', $companyId)->first();

        $defaults = \Database\Seeders\PricingConfigSeeder::getDefaults();

        $pricingConfig = [];
        foreach ($defaults as $key => $defaultValue) {
            $saved = $settings?->$key;
            $pricingConfig[$key] = $saved !== null ? $saved : $defaultValue;
        }

        return response()->json(array_merge([
            'telegram_trigger_status_id' => $settings->telegram_trigger_status_id ?? null,
            'telegram_accepted_status_id' => $settings->telegram_accepted_status_id ?? null,
            'telegram_closed_ok_status_id' => $settings->telegram_closed_ok_status_id ?? null,
            'telegram_closed_ko_status_id' => $settings->telegram_closed_ko_status_id ?? null,
            'telegram_collected_status_id' => $settings->telegram_collected_status_id ?? null,
            'telegram_location_status_ids' => $settings->telegram_location_status_ids ?? [],
            'service_cancel_status_ids' => $settings->service_cancel_status_ids ?? [],
            'activity_confirmation_user_ids' => $settings->activity_confirmation_user_ids ?? [],
            'activity_confirmation_default_user_id' => $settings->activity_confirmation_default_user_id ?? null,
            'deposit_percentage' => $settings->deposit_percentage ?? 30,
            'card_fees_percentage' => $settings->card_fees_percentage ?? 5,
        ], $pricingConfig));
    }
}
