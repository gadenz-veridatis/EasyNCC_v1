<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'reference_number',
        'client_id',
        'intermediary_id',
        'supplier_id',
        'passenger_count',
        'contact_name',
        'contact_phone',
        'service_type',
        'vehicle_type',
        'vehicle_id',
        'vehicle_not_replaceable',
        'external_driver_name',
        'external_driver_phone',
        'driver_not_replaceable',
        'dress_code_id',
        'large_luggage',
        'medium_luggage',
        'small_luggage',
        'baby_seat_infant',
        'baby_seat_standard',
        'baby_seat_booster',
        'pickup_datetime',
        'pickup_location',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'vehicle_departure_datetime',
        'dropoff_datetime',
        'dropoff_location',
        'dropoff_address',
        'dropoff_latitude',
        'dropoff_longitude',
        'vehicle_return_datetime',
        'status_id',
        'driver_must_collect',
        'service_price',
        'vat_rate',
        'card_fees_percentage',
        'deposit_percentage',
        'deposit_taxable',
        'deposit_handling_fees',
        'deposit_amount',
        'balance_taxable',
        'balance_handling_fees',
        'balance_card_fees',
        'balance_sale_type',
        'driver_compensation',
        'intermediary_commission',
        'expenses',
        'fuel_cost',
        'toll_cost',
        'parking_cost',
        'other_vehicle_costs',
        'colleague_cost',
        'notes',
        'transaction_status_map',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'pickup_datetime' => 'datetime',
        'vehicle_departure_datetime' => 'datetime',
        'dropoff_datetime' => 'datetime',
        'vehicle_return_datetime' => 'datetime',
        'passenger_count' => 'integer',
        'vehicle_not_replaceable' => 'boolean',
        'driver_not_replaceable' => 'boolean',
        'driver_must_collect' => 'boolean',
        'large_luggage' => 'integer',
        'medium_luggage' => 'integer',
        'small_luggage' => 'integer',
        'baby_seat_infant' => 'integer',
        'baby_seat_standard' => 'integer',
        'baby_seat_booster' => 'integer',
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'dropoff_latitude' => 'decimal:8',
        'dropoff_longitude' => 'decimal:8',
        'service_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'card_fees_percentage' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
        'deposit_taxable' => 'decimal:2',
        'deposit_handling_fees' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'balance_taxable' => 'decimal:2',
        'balance_handling_fees' => 'decimal:2',
        'balance_card_fees' => 'decimal:2',
        'driver_compensation' => 'decimal:2',
        'intermediary_commission' => 'decimal:2',
        'expenses' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'toll_cost' => 'decimal:2',
        'parking_cost' => 'decimal:2',
        'other_vehicle_costs' => 'decimal:2',
        'colleague_cost' => 'decimal:2',
        'transaction_status_map' => 'array',
    ];

    // Relationships
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function intermediary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'intermediary_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function dressCode(): BelongsTo
    {
        return $this->belongsTo(DressCode::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ServiceStatus::class);
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_driver');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(ServicePassenger::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(ServiceStop::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServicePayment::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(ServiceCost::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function accountingTransactions(): HasMany
    {
        return $this->hasMany(AccountingTransaction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ServiceAttachment::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get overlaps where this service is the primary service.
     */
    public function overlaps(): HasMany
    {
        return $this->hasMany(ServiceOverlap::class, 'service_id');
    }

    /**
     * Get overlaps where this service is the overlapping service.
     */
    public function overlappedBy(): HasMany
    {
        return $this->hasMany(ServiceOverlap::class, 'overlapping_service_id');
    }

    /**
     * Rebuild the transaction_status_map from current accounting transactions.
     *
     * Produces semantic keys mapped to status codes:
     * - "deposit_amount" → status of the sale deposit transaction
     * - "balance" → status of the sale balance transaction
     * - "driver_compensation" → status of the driver cost transaction
     * - "fuel_cost", "toll_cost", etc. → individual cost statuses
     * - "intermediary_commission" → intermediation status
     * - Aggregate: "sale", "purchase" → worst status across group
     */
    public function refreshTransactionStatusMap(): void
    {
        $transactions = $this->accountingTransactions()
            ->whereNotNull('status')
            ->select('transaction_type', 'installment', 'accounting_entry_id', 'status')
            ->get();

        if ($transactions->isEmpty()) {
            $this->update(['transaction_status_map' => null]);
            return;
        }

        // Load company settings to map entry_ids to semantic field names
        $settings = Settings::where('company_id', $this->company_id)->first();

        // Build reverse map: accounting_entry_id → semantic field name
        $entryToField = [];
        if ($settings) {
            $fieldMap = [
                'deposit_accounting_entry_id' => 'deposit_amount',
                'balance_accounting_entry_id' => 'balance',
                'driver_cost_accounting_entry_id' => 'driver_compensation',
                'colleague_cost_accounting_entry_id' => 'colleague_cost',
                'commission_accounting_entry_id' => 'intermediary_commission',
                'fuel_accounting_entry_id' => 'fuel_cost',
                'toll_accounting_entry_id' => 'toll_cost',
                'parking_accounting_entry_id' => 'parking_cost',
                'other_vehicle_accounting_entry_id' => 'other_vehicle_costs',
                'experience_accounting_entry_id' => 'experience_cost',
                'handling_fees_accounting_entry_id' => 'handling_fees',
                'card_fees_accounting_entry_id' => 'card_fees',
            ];

            foreach ($fieldMap as $settingsField => $semanticName) {
                $entryId = $settings->$settingsField;
                if ($entryId) {
                    $entryToField[$entryId] = $semanticName;
                }
            }
        }

        $statusPriority = [
            'suspended' => 0,
            'cancelled' => 1,
            'to_pay' => 2,
            'to_collect' => 2,
            'collected_driver' => 3,
            'paid' => 4,
            'collected' => 4,
        ];

        $map = [];
        $groups = []; // group_key => [statuses]

        foreach ($transactions as $t) {
            // Semantic key from settings mapping
            $semanticKey = null;
            if ($t->accounting_entry_id && isset($entryToField[$t->accounting_entry_id])) {
                $fieldName = $entryToField[$t->accounting_entry_id];
                // Disambiguate deposit vs balance for same entry_id (e.g. both use "Ricavo Servizio")
                if ($t->installment === 'deposit' && $fieldName === 'deposit_amount') {
                    $semanticKey = 'deposit_amount';
                } elseif ($t->installment === 'balance' && $fieldName === 'balance') {
                    $semanticKey = 'balance';
                } elseif ($t->installment === 'deposit' && in_array($fieldName, ['handling_fees', 'card_fees'])) {
                    $semanticKey = 'deposit_' . $fieldName;
                } elseif ($t->installment === 'balance' && in_array($fieldName, ['handling_fees', 'card_fees'])) {
                    $semanticKey = 'balance_' . $fieldName;
                } else {
                    $semanticKey = $fieldName;
                }
            }

            // Write semantic key
            if ($semanticKey) {
                $map[$semanticKey] = $t->status;
            }

            // Aggregate keys by type+installment and by type
            $typeInstallmentKey = $t->transaction_type . '_' . $t->installment;
            $typeKey = $t->transaction_type;
            $groups[$typeInstallmentKey][] = $t->status;
            $groups[$typeKey][] = $t->status;
        }

        // Compute worst status per aggregate group
        foreach ($groups as $groupKey => $statuses) {
            $worst = null;
            $worstPriority = PHP_INT_MAX;
            foreach ($statuses as $s) {
                $p = $statusPriority[$s] ?? 5;
                if ($p < $worstPriority) {
                    $worstPriority = $p;
                    $worst = $s;
                }
            }
            if ($worst) {
                $map[$groupKey] = $worst;
            }
        }

        $this->update(['transaction_status_map' => $map]);
    }
}
