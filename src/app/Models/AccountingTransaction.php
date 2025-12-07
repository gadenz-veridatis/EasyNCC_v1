<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'service_id',
        'transaction_date',
        'amount',
        'transaction_type',
        'accounting_entry_id',
        'installment',
        'counterpart_id',
        'document_number',
        'document_due_date',
        'payment_date',
        'payment_type',
        'payment_reason',
        'iban',
        'status',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'document_due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the company that owns the transaction.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the service associated with the transaction.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the accounting entry (causale) associated with the transaction.
     */
    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class);
    }

    /**
     * Get the counterpart user (fornitore/intermediario/committente).
     */
    public function counterpart(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counterpart_id');
    }
}
