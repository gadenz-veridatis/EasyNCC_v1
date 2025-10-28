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
        'name',
        'registration_number',
        'vin',
        'model',
        'manufacturer',
        'year',
        'color',
        'passenger_capacity',
        'allow_overlapping',
        'purchase_date',
        'fuel_type',
        'euro_class',
        'notes',
    ];

    protected $casts = [
        'passenger_capacity' => 'integer',
        'allow_overlapping' => 'boolean',
        'purchase_date' => 'date',
    ];

    // Relationships
    public function assignedDrivers(): HasMany
    {
        return $this->hasMany(DriverProfile::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
