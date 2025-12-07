<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'surname',
        'name',
        'phone',
        'email',
        'nationality',
        'origin',
        'carrier_reference',
    ];

    // Relationships
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
