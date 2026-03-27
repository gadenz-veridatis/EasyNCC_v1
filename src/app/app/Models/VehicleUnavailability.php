<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleUnavailability extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'vehicle_unavailability_type_id',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function unavailabilityType(): BelongsTo
    {
        return $this->belongsTo(VehicleUnavailabilityType::class, 'vehicle_unavailability_type_id');
    }
}
