<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOverlap extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_id',
        'overlapping_service_id',
        'overlap_type',
        'driver_id',
        'vehicle_id',
        'confirmed_by_user',
        'created_at',
    ];

    protected $casts = [
        'confirmed_by_user' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the service that has the overlap.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the overlapping service.
     */
    public function overlappingService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'overlapping_service_id');
    }

    /**
     * Get the driver involved in the overlap.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Get the vehicle involved in the overlap.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
