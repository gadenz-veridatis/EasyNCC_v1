<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'license_plate',
        'brand',
        'model',
        'passenger_capacity',
        'purchase_date',
        'ncc_license_number',
        'telepass_license_number',
        'license_city',
        'allow_overlapping',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'passenger_capacity' => 'integer',
        'allow_overlapping' => 'boolean',
        'purchase_date' => 'date',
    ];

    // Relationships
    public function assignedDrivers(): HasMany
    {
        return $this->hasMany(DriverProfile::class, 'assigned_vehicle_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function vehicleAttachments(): HasMany
    {
        return $this->hasMany(VehicleAttachment::class);
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(VehicleUnavailability::class);
    }

    // Audit relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
