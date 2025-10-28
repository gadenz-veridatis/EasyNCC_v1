<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'vat_number',
        'sdi',
        'pec',
        'address',
        'website',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function dressCodes(): HasMany
    {
        return $this->hasMany(DressCode::class);
    }

    public function luggageTypes(): HasMany
    {
        return $this->hasMany(LuggageType::class);
    }

    public function paymentTypes(): HasMany
    {
        return $this->hasMany(PaymentType::class);
    }

    public function driverAttachmentTypes(): HasMany
    {
        return $this->hasMany(DriverAttachmentType::class);
    }

    public function vehicleAttachmentTypes(): HasMany
    {
        return $this->hasMany(VehicleAttachmentType::class);
    }

    public function serviceStatuses(): HasMany
    {
        return $this->hasMany(ServiceStatus::class);
    }

    public function ztls(): HasMany
    {
        return $this->hasMany(Ztl::class);
    }
}
