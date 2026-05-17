<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settings extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'deposit_percentage',
        'card_fees_percentage',
        'deposit_accounting_entry_id',
        'deposit_reason',
        'balance_accounting_entry_id',
        'balance_reason',
        'activity_confirmation_text',
        'activity_confirmation_role',
        'activity_confirmation_user_ids',
        'activity_confirmation_default_user_id',
        'default_supplier_id',
        'commission_accounting_entry_id',
        'commission_reason',
        'fuel_accounting_entry_id',
        'fuel_reason',
        'toll_accounting_entry_id',
        'toll_reason',
        'parking_accounting_entry_id',
        'parking_reason',
        'other_vehicle_accounting_entry_id',
        'other_vehicle_reason',
        'driver_cost_accounting_entry_id',
        'driver_cost_reason',
        'colleague_cost_accounting_entry_id',
        'colleague_cost_reason',
        'experience_accounting_entry_id',
        'experience_reason',
        'handling_fees_accounting_entry_id',
        'handling_fees_reason',
        'card_fees_accounting_entry_id',
        'card_fees_reason',
        'extra_revenue_accounting_entry_id',
        'extra_revenue_reason',
        'telegram_trigger_status_id',
        'telegram_accepted_status_id',
        'telegram_closed_ok_status_id',
        'telegram_closed_ko_status_id',
        'telegram_collected_status_id',
        'telegram_location_status_ids',
        'service_cancel_status_ids',
        'email_accepted_status_id',
        'email_closed_status_id',
        'email_notification_address',
        'email_assignment_template_id',
        'email_closure_template_id',
        'email_gmail_account_id',
        'email_token_expiry_days',
        'aviationstack_api_key',
        'flight_tracking_enabled',
        'pricing_markups',
        'pricing_vehicle_costs',
        'pricing_vehicle_assumptions',
        'pricing_annual_expenses',
        'pricing_season_service',
        'pricing_vehicle_service',
        'pricing_season_experience',
        'pricing_vehicle_experience',
        'pricing_attenuation_transport',
        'pricing_attenuation_driver',
        'pricing_extension',
        'pricing_depreciation',
        'pricing_toll',
        'gmail_label_richieste',
        'gmail_subject_tag',
        'gmail_polling_interval',
    ];

    protected $casts = [
        'deposit_percentage' => 'decimal:2',
        'card_fees_percentage' => 'decimal:2',
        'telegram_location_status_ids' => 'array',
        'service_cancel_status_ids' => 'array',
        'activity_confirmation_user_ids' => 'array',
        'flight_tracking_enabled' => 'boolean',
        'pricing_markups' => 'array',
        'pricing_vehicle_costs' => 'array',
        'pricing_vehicle_assumptions' => 'array',
        'pricing_annual_expenses' => 'array',
        'pricing_season_service' => 'array',
        'pricing_vehicle_service' => 'array',
        'pricing_season_experience' => 'array',
        'pricing_vehicle_experience' => 'array',
        'pricing_attenuation_transport' => 'array',
        'pricing_attenuation_driver' => 'array',
        'pricing_extension' => 'array',
        'pricing_depreciation' => 'array',
        'pricing_toll' => 'array',
        'gmail_polling_interval' => 'integer',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function depositAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'deposit_accounting_entry_id');
    }

    public function balanceAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'balance_accounting_entry_id');
    }

    public function defaultSupplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_supplier_id');
    }

    public function commissionAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'commission_accounting_entry_id');
    }

    public function fuelAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'fuel_accounting_entry_id');
    }

    public function tollAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'toll_accounting_entry_id');
    }

    public function parkingAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'parking_accounting_entry_id');
    }

    public function otherVehicleAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'other_vehicle_accounting_entry_id');
    }

    public function driverCostAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'driver_cost_accounting_entry_id');
    }

    public function colleagueCostAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'colleague_cost_accounting_entry_id');
    }

    public function experienceAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'experience_accounting_entry_id');
    }

    public function handlingFeesAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'handling_fees_accounting_entry_id');
    }

    public function cardFeesAccountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'card_fees_accounting_entry_id');
    }

    /**
     * Status che triggera invio notifica Telegram.
     */
    public function telegramTriggerStatus(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class, 'telegram_trigger_status_id');
    }

    /**
     * Status da impostare quando driver accetta via Telegram.
     */
    public function telegramAcceptedStatus(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class, 'telegram_accepted_status_id');
    }

    /**
     * Status da impostare quando driver chiude servizio OK via Telegram.
     */
    public function telegramClosedOkStatus(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class, 'telegram_closed_ok_status_id');
    }

    /**
     * Status da impostare quando driver chiude servizio KO via Telegram.
     */
    public function telegramClosedKoStatus(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class, 'telegram_closed_ko_status_id');
    }

    /**
     * Status da applicare alla transazione contabile quando il driver segnala incasso avvenuto via Telegram.
     */
    public function telegramCollectedStatus(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class, 'telegram_collected_status_id');
    }

    // Email notification relationships

    public function emailAcceptedStatus(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class, 'email_accepted_status_id');
    }

    public function emailClosedStatus(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class, 'email_closed_status_id');
    }

    public function emailAssignmentTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteEmailTemplate::class, 'email_assignment_template_id');
    }

    public function emailClosureTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteEmailTemplate::class, 'email_closure_template_id');
    }

    public function emailGmailAccount(): BelongsTo
    {
        return $this->belongsTo(GmailAccount::class, 'email_gmail_account_id');
    }
}
