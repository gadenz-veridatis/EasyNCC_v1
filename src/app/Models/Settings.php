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
    ];

    protected $casts = [
        'deposit_percentage' => 'decimal:2',
        'card_fees_percentage' => 'decimal:2',
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
}
