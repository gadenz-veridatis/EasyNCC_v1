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
}
