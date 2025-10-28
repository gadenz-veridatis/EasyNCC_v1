<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BusinessContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contactable_type',
        'contactable_id',
        'type',
        'value',
        'notes',
    ];

    // Relationships
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }
}
