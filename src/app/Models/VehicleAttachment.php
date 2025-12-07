<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAttachment extends Model
{
    protected $fillable = [
        'vehicle_id',
        'attachment_type',
        'expiration_date',
        'notes',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'file_size' => 'integer',
    ];

    /**
     * Get the vehicle that owns the attachment
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
