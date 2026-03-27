<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_vehicle_id',
        'profile_photo',
        'birth_date',
        'fiscal_code',
        'vat_number',
        'hourly_rate',
        'bank_name',
        'iban',
        'allow_overlapping',
        'color',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'allow_overlapping' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
