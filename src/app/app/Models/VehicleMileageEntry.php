<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMileageEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'mileage',
        'entry_date',
        'update_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'mileage' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
