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
        'vehicle_id',
        'client_id',
        'intermediary_id',
        'supplier_id',
        'dress_code_id',
        'status_id',
        'pickup_location',
        'pickup_datetime',
        'dropoff_location',
        'dropoff_datetime',
        'passenger_count',
        'vehicle_not_replaceable',
        'driver_not_replaceable',
        'bagagli',
        'baby_seat',
        'notes',
        'total_amount',
        'vat_amount',
    ];

    protected $casts = [
        'pickup_datetime' => 'datetime',
        'dropoff_datetime' => 'datetime',
        'passenger_count' => 'integer',
        'vehicle_not_replaceable' => 'boolean',
        'driver_not_replaceable' => 'boolean',
        'bagagli' => 'integer',
        'baby_seat' => 'integer',
        'total_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
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
}
