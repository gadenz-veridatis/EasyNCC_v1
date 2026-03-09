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
        'telegram_trigger_status_id',
        'telegram_accepted_status_id',
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
    ];

    protected $casts = [
        'deposit_percentage' => 'decimal:2',
        'card_fees_percentage' => 'decimal:2',
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
}
