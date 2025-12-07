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
        'service_price',
        'vat_rate',
        'card_fees_percentage',
        'deposit_percentage',
        'deposit_amount',
        'balance_taxable',
        'balance_handling_fees',
        'balance_card_fees',
        'driver_compensation',
        'intermediary_commission',
        'expenses',
        'notes',
    ];

    protected $casts = [
        'pickup_datetime' => 'datetime',
        'vehicle_departure_datetime' => 'datetime',
        'dropoff_datetime' => 'datetime',
        'vehicle_return_datetime' => 'datetime',
        'passenger_count' => 'integer',
        'vehicle_not_replaceable' => 'boolean',
        'driver_not_replaceable' => 'boolean',
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
        'deposit_amount' => 'decimal:2',
        'balance_taxable' => 'decimal:2',
        'balance_handling_fees' => 'decimal:2',
        'balance_card_fees' => 'decimal:2',
        'driver_compensation' => 'decimal:2',
        'intermediary_commission' => 'decimal:2',
        'expenses' => 'decimal:2',
    ];

    // Relationships
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
